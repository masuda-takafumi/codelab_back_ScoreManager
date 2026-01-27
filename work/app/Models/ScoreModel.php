<?php

/*
 * 役割: 生徒の成績データ取得と画面表示。
 * 目次:
 *  - getStudent: 生徒1名の基本情報を取得
 *  - getTestsWithScores: テスト一覧と科目別得点を取得
 *  - buildScoreViewData: 合計/平均/テスト種別など表示用に整え
 */
class ScoreModel
{
    public static function getStudent(PDO $pdo, $studentId)
    {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }

    public static function getTestsWithScores(PDO $pdo, $studentId)
    {
        $sql = "SELECT 
                    t.id AS test_id,
                    t.test_date,
                    t.test_cd,
                    COUNT(sc.id) as score_count,
                    COALESCE(MAX(CASE WHEN s.id = 3 AND sc.student_id = ? THEN sc.score END), 0) as japanese,
                    COALESCE(MAX(CASE WHEN s.id = 2 AND sc.student_id = ? THEN sc.score END), 0) as math,
                    COALESCE(MAX(CASE WHEN s.id = 1 AND sc.student_id = ? THEN sc.score END), 0) as english,
                    COALESCE(MAX(CASE WHEN s.id = 4 AND sc.student_id = ? THEN sc.score END), 0) as science,
                    COALESCE(MAX(CASE WHEN s.id = 5 AND sc.student_id = ? THEN sc.score END), 0) as social
                FROM tests t
                LEFT JOIN scores sc ON t.id = sc.test_id AND sc.student_id = ?
                LEFT JOIN subjects s ON sc.subject_id = s.id
                GROUP BY t.id, t.test_date, t.test_cd
                ORDER BY t.test_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentId, $studentId, $studentId, $studentId, $studentId, $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buildScoreViewData(array $rows)
    {
        foreach ($rows as &$score) {
            $scores = array_filter(
                [
                    $score['japanese'] ?? 0,
                    $score['math'] ?? 0,
                    $score['english'] ?? 0,
                    $score['science'] ?? 0,
                    $score['social'] ?? 0
                ],
                function ($v) { return $v !== null && $v !== ''; }
            );
            $score['total'] = array_sum($scores);
            $score['avg'] = count($scores) > 0 ? number_format($score['total'] / count($scores), 1) : '0.0';
            $score_count = (int)($score['score_count'] ?? 0);

            if ($score_count === 0) {
                $score['test_type'] = '未受験';
            } else {
                if ((int)($score['test_cd'] ?? 0) === 1) {
                    $score['test_type'] = '中間試験';
                } else {
                    $score['test_type'] = '期末試験';
                }
            }
        }
        unset($score);

        return $rows;
    }

    public static function replaceScores(PDO $pdo, int $studentId, int $testId, array $normalizedScores): void
    {
        $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ? AND test_id = ?");
        $stmt->execute([$studentId, $testId]);

        $subjects = [
            'english' => 1,
            'math' => 2,
            'japanese' => 3,
            'science' => 4,
            'social' => 5
        ];
        foreach ($subjects as $subject => $subjectId) {
            $score_value = $normalizedScores[$subject] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO scores (student_id, test_id, subject_id, score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$studentId, $testId, $subjectId, $score_value]);
        }
    }

    public static function deleteScores(PDO $pdo, int $studentId, int $testId): void
    {
        $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ? AND test_id = ?");
        $stmt->execute([$studentId, $testId]);
    }
}
