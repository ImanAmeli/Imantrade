<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class SurveyController extends AdminController
{
    public function index(): void
    {
        $questions = Database::all(
            'SELECT * FROM survey_questions WHERE tenant_id = ? ORDER BY sort_order, id',
            [$this->tenantId]
        );
        foreach ($questions as &$q) {
            $q['options'] = Database::all('SELECT * FROM survey_options WHERE question_id = ? ORDER BY sort_order, id', [$q['id']]);
            $q['responses'] = (int) Database::scalar('SELECT COUNT(*) FROM survey_answers WHERE question_id = ?', [$q['id']]);
        }
        unset($q);
        $totalResponses = (int) Database::scalar('SELECT COUNT(*) FROM survey_responses WHERE tenant_id = ?', [$this->tenantId]);
        $this->view('admin/surveys', compact('questions', 'totalResponses'));
    }

    public function store(): void
    {
        $this->guardCsrf('admin/surveys');
        $question = trim($_POST['question'] ?? '');
        $options = array_filter(array_map('trim', $_POST['options'] ?? []), fn ($o) => $o !== '');
        if ($question === '' || count($options) < 2) {
            flash('error', 'سوال و حداقل دو گزینه لازم است.');
            redirect('admin/surveys');
        }
        $qid = Database::insert(
            'INSERT INTO survey_questions (tenant_id, question, sort_order) VALUES (?,?,?)',
            [$this->tenantId, $question, (int) ($_POST['sort_order'] ?? 0)]
        );
        $i = 0;
        foreach ($options as $opt) {
            Database::insert('INSERT INTO survey_options (question_id, label, sort_order) VALUES (?,?,?)', [$qid, $opt, $i++]);
        }
        flash('success', 'سوال اضافه شد.');
        redirect('admin/surveys');
    }

    public function destroy(string $id): void
    {
        $this->guardCsrf('admin/surveys');
        Database::run('DELETE FROM survey_questions WHERE id = ? AND tenant_id = ?', [(int) $id, $this->tenantId]);
        flash('success', 'سوال حذف شد.');
        redirect('admin/surveys');
    }

    /** Seed 20 ready-made multiple-choice questions for a café/restaurant. */
    public function seedDefaults(): void
    {
        $this->guardCsrf('admin/surveys');
        $existing = (int) Database::scalar('SELECT COUNT(*) FROM survey_questions WHERE tenant_id = ?', [$this->tenantId]);
        if ($existing > 0) {
            flash('error', 'قبلاً سوال دارید؛ برای جلوگیری از تکرار، seed انجام نشد.');
            redirect('admin/surveys');
        }
        foreach (self::defaultQuestions() as $i => $q) {
            $qid = Database::insert(
                'INSERT INTO survey_questions (tenant_id, question, sort_order) VALUES (?,?,?)',
                [$this->tenantId, $q['q'], $i]
            );
            foreach ($q['opts'] as $j => $opt) {
                Database::insert('INSERT INTO survey_options (question_id, label, sort_order) VALUES (?,?,?)', [$qid, $opt, $j]);
            }
        }
        flash('success', '۲۰ سوال نمونه اضافه شد.');
        redirect('admin/surveys');
    }

    private static function defaultQuestions(): array
    {
        $freq = ['اولین بار', 'گاهی', 'ماهی چند بار', 'هفتگی یا بیشتر'];
        $rate = ['عالی', 'خوب', 'متوسط', 'ضعیف'];
        return [
            ['q' => 'چند وقت یک‌بار به ما سر می‌زنید؟', 'opts' => $freq],
            ['q' => 'کیفیت غذا/نوشیدنی را چطور ارزیابی می‌کنید؟', 'opts' => $rate],
            ['q' => 'سرعت سرویس‌دهی چطور بود؟', 'opts' => $rate],
            ['q' => 'برخورد پرسنل را چطور دیدید؟', 'opts' => $rate],
            ['q' => 'تمیزی محیط را چطور ارزیابی می‌کنید؟', 'opts' => $rate],
            ['q' => 'قیمت‌ها نسبت به کیفیت چطور است؟', 'opts' => ['کاملاً منصفانه', 'منصفانه', 'کمی گران', 'گران']],
            ['q' => 'بیشتر کدام وعده را سفارش می‌دهید؟', 'opts' => ['صبحانه', 'ناهار', 'عصرانه', 'شام']],
            ['q' => 'علاقه‌مند به کدام دسته هستید؟', 'opts' => ['نوشیدنی گرم', 'نوشیدنی سرد', 'غذای اصلی', 'دسر']],
            ['q' => 'تنوع منو را چطور می‌بینید؟', 'opts' => $rate],
            ['q' => 'فضای داخلی را چطور ارزیابی می‌کنید؟', 'opts' => $rate],
            ['q' => 'موسیقی محیط چطور بود؟', 'opts' => $rate],
            ['q' => 'چطور با ما آشنا شدید؟', 'opts' => ['دوستان', 'اینستاگرام', 'عبور از مقابل', 'سایر']],
            ['q' => 'تمایل به سفارش آنلاین/بیرون‌بر دارید؟', 'opts' => ['بله همیشه', 'گاهی', 'به‌ندرت', 'خیر']],
            ['q' => 'کدام مورد برایتان مهم‌تر است؟', 'opts' => ['کیفیت', 'قیمت', 'سرعت', 'فضا']],
            ['q' => 'به برنامه باشگاه مشتریان علاقه دارید؟', 'opts' => ['خیلی زیاد', 'زیاد', 'کم', 'خیر']],
            ['q' => 'دریافت تخفیف تولد برایتان جذاب است؟', 'opts' => ['خیلی زیاد', 'زیاد', 'کم', 'خیر']],
            ['q' => 'تمایل دارید پیشنهادها را از چه راهی بگیرید؟', 'opts' => ['پیامک', 'تلگرام', 'بله', 'تماس نگیرید']],
            ['q' => 'ما را به دیگران پیشنهاد می‌دهید؟', 'opts' => ['حتماً', 'احتمالاً', 'شاید', 'خیر']],
            ['q' => 'کدام ساعت برای حضور ترجیح می‌دهید؟', 'opts' => ['صبح', 'ظهر', 'عصر', 'شب']],
            ['q' => 'رضایت کلی شما چقدر است؟', 'opts' => $rate],
        ];
    }
}
