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
    private const DEFAULT_PHOTO_PATH = '/img/ダミー生徒画像.png';

    private static function buildStudentParams(array $data): array
    {
        $params = [];
        foreach (self::STUDENT_COLUMNS as $field) {
            $params[] = $data[$field];
        }
        return $params;
    }

    private static function createDefaultDetailStudent($studentId = ''): array
    {
        return [
            'id' => (string)$studentId,
            'class' => '',
            'class_no' => '',
            'last_name' => '',
            'first_name' => '',
            'last_name_kana' => '',
            'first_name_kana' => '',
            'gender' => '',
            'birth_date' => '',
            'birth_year' => '',
            'birth_month' => '',
            'birth_day' => '',
            'gender_text' => ''
        ];
    }

    private static function resolvePhotoPath($studentId): string
    {
        if ($studentId !== '' && ctype_digit((string)$studentId)) {
            $photo_file = '/work/public/img/student_' . intval($studentId) . '.jpg';
            if (file_exists($photo_file)) {
                return '/img/student_' . intval($studentId) . '.jpg';
            }
        }
        return self::DEFAULT_PHOTO_PATH;
    }

    private static function buildDetailReturnUrl(array $queryParams): string
    {
        $return_params = [];
        if (isset($queryParams['q']) && $queryParams['q'] !== '') {
            $return_params['q'] = $queryParams['q'];
        }
        if (isset($queryParams['page']) && ctype_digit((string)$queryParams['page'])) {
            $return_params['page'] = $queryParams['page'];
        }
        return buildUrl('/student_list.php', $return_params);
    }

    public static function getDefaultDetailViewData(array $queryParams = []): array
    {
        return [
            'student' => self::createDefaultDetailStudent(),
            'photo_path' => self::DEFAULT_PHOTO_PATH,
            'return_url' => self::buildDetailReturnUrl($queryParams)
        ];
    }

    public static function getDetailViewData(PDO $pdo, $studentId, array $queryParams = []): array
    {
        $view_data = self::getDefaultDetailViewData($queryParams);
        $student = self::createDefaultDetailStudent($studentId);

        if ($studentId !== '' && ctype_digit((string)$studentId)) {
            $student_data = self::getStudentViewData($pdo, $studentId);
            if ($student_data) {
                $student = array_merge($student, $student_data);
            }
        }

        $view_data['student'] = $student;
        $view_data['photo_path'] = self::resolvePhotoPath($student['id']);
        return $view_data;
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
