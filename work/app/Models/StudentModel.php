<?php

/*
 * 役割: 生徒情報の取得と一覧表示向け。
 * 目次:
 *  - getStudentViewData: 生徒詳細の表示用データ
 *  - getStudentListData: 検索/ページング込みの一覧データを取得
 */
class StudentModel
{
    private const STUDENT_COLUMNS = ['last_name', 'first_name', 'last_name_kana', 'first_name_kana', 'class', 'class_no', 'gender', 'birth_date'];

    private static function buildStudentParams(array $data): array
    {
        $params = [];
        foreach (self::STUDENT_COLUMNS as $field) {
            $params[] = $data[$field];
        }
        return $params;
    }

    public static function getStudentViewData(PDO $pdo, $studentId)
    {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        if (!$student) {
            return null;
        }

        $gender = $student['gender'] ?? '';
        $student['gender_text'] = ($gender == '1') ? '男' : (($gender == '2') ? '女' : '');
        $student['birth_year'] = '';
        $student['birth_month'] = '';
        $student['birth_day'] = '';
        $birth_date = $student['birth_date'] ?? '';
        $date_parts = explode('-', $birth_date);
        if (count($date_parts) === 3) {
            $student['birth_year'] = $date_parts[0];
            $student['birth_month'] = $date_parts[1];
            $student['birth_day'] = $date_parts[2];
        }

        return $student;
    }

    public static function getStudentListData(PDO $pdo, $searchQuery, $pageParam, $newParam, $itemsPerPage)
    {
        $where_sql = '';
        $where_params = [];
        if ($searchQuery !== '') {
            $where_sql = "WHERE last_name LIKE ? OR first_name LIKE ? OR last_name_kana LIKE ? OR first_name_kana LIKE ?";
            $like_query = '%' . $searchQuery . '%';
            $where_params = [$like_query, $like_query, $like_query, $like_query];
        }

        $current_page = (ctype_digit((string)$pageParam) && (int)$pageParam > 0) ? (int)$pageParam : 1;

        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students $where_sql");
        $count_stmt->execute($where_params);
        $total_students = (int)$count_stmt->fetchColumn();
        $total_pages = (int)ceil($total_students / $itemsPerPage);
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        if ($newParam === '1' && $pageParam === '') {
            $current_page = $total_pages;
        }
        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }

        $page_buttons = buildPaginationButtons($current_page, $total_pages);

        $offset = ($current_page - 1) * $itemsPerPage;
        $stmt = $pdo->prepare("SELECT * FROM students $where_sql ORDER BY class, class_no LIMIT ? OFFSET ?");
        foreach ($where_params as $index => $value) {
            $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(count($where_params) + 1, $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(count($where_params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'students' => $students,
            'total_students' => $total_students,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'page_buttons' => $page_buttons
        ];
    }

    public static function insertStudent(PDO $pdo, array $data): int
    {
        $columns = implode(', ', self::STUDENT_COLUMNS);
        $placeholders = implode(', ', array_fill(0, count(self::STUDENT_COLUMNS), '?'));
        $stmt = $pdo->prepare("INSERT INTO students ($columns) VALUES ($placeholders)");
        $stmt->execute(self::buildStudentParams($data));
        return (int)$pdo->lastInsertId();
    }

    public static function updateStudent(PDO $pdo, int $studentId, array $data): void
    {
        $set_clause_parts = [];
        foreach (self::STUDENT_COLUMNS as $column) {
            $set_clause_parts[] = $column . ' = ?';
        }
        $set_clause = implode(', ', $set_clause_parts);
        $stmt = $pdo->prepare("UPDATE students SET $set_clause WHERE id = ?");
        $execute = self::buildStudentParams($data);
        $execute[] = $studentId;
        $stmt->execute($execute);
    }

    public static function deleteStudentWithScores(PDO $pdo, int $studentId): void
    {
        $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ?");
        $stmt->execute([$studentId]);

        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
    }
}
