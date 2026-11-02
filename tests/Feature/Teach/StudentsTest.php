<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentsTest extends TestCase
{
    public function test_one_row_per_student_across_the_instructors_courses(): void
    {
        [$owner, $math, $physics] = $this->owner();
        $sara = User::factory()->create(['name' => 'سارة أحمد', 'phone' => '+218911111111']);
        $omar = User::factory()->create(['name' => 'عمر علي', 'phone' => '+218922222222']);

        $this->accept($sara, $math, '300.000');
        $this->accept($sara, $physics, '200.000');
        $this->accept($omar, $math, '300.000');

        $this->actingAs($owner)->get('/teach/students')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Teach/Students')
            ->where('counts', ['active' => 2, 'past' => 0, 'all' => 2])
            ->has('students.data', 2)
            ->where('students.data.0.name', 'سارة أحمد')
            ->where('students.data.0.courses_count', 2)
            ->where('students.data.0.active_count', 2)
            ->where('students.data.0.remaining', '500')
            ->where('students.data.0.whatsapp', fn ($url) => str_starts_with($url, 'https://wa.me/218911111111'))
            ->where('students.data.1.name', 'عمر علي')
            ->where('totals.remaining', '800')
            // Internal helper fields are not sent to the page.
            ->missing('students.data.0.phone_e164')
            ->missing('students.data.0.remaining_millis'));
    }

    public function test_only_accepted_people_are_students(): void
    {
        [$owner, $math] = $this->owner();

        Enrollment::factory()->for($math, 'teachingClass')->create(); // requested
        $rejected = Enrollment::factory()->for($math, 'teachingClass')->create();
        $rejected->forceFill(['status' => Enrollment::STATUS_REJECTED])->save();
        $this->accept(User::factory()->create(), $math);

        $this->actingAs($owner)->get('/teach/students?filter=all')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('counts.all', 1));
    }

    public function test_another_instructors_students_are_not_listed_and_shared_students_show_only_this_part(): void
    {
        [$owner, $math] = $this->owner();
        [, $otherClass] = $this->owner();
        $shared = User::factory()->create(['name' => 'مشترك']);
        $this->accept($shared, $math, '100.000');
        $this->accept($shared, $otherClass, '999.000');
        $this->accept(User::factory()->create(['name' => 'غريب']), $otherClass);

        $this->actingAs($owner)->get('/teach/students')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.name', 'مشترك')
            ->where('students.data.0.courses_count', 1)
            ->where('students.data.0.remaining', '100')
            ->has('students.data.0.enrollments', 1));
    }

    public function test_active_and_past_tabs(): void
    {
        [$owner, $math, $physics] = $this->owner();
        $active = User::factory()->create(['name' => 'حالي']);
        $removed = User::factory()->create(['name' => 'مُخرَج']);
        $inCanceled = User::factory()->create(['name' => 'دورة ملغاة']);

        $this->accept($active, $math);
        $this->accept($removed, $math)->forceFill(['status' => Enrollment::STATUS_CANCELED])->save();
        $this->accept($inCanceled, $physics);
        $physics->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();

        $this->actingAs($owner);
        $this->get('/teach/students')->assertInertia(fn (Assert $page) => $page
            ->where('counts', ['active' => 1, 'past' => 2, 'all' => 3])
            ->has('students.data', 1)
            ->where('students.data.0.name', 'حالي'));
        $this->get('/teach/students?filter=past')->assertInertia(fn (Assert $page) => $page->has('students.data', 2));
        $this->get('/teach/students?filter=all')->assertInertia(fn (Assert $page) => $page->has('students.data', 3));
        $this->get('/teach/students?filter=nonsense')->assertSessionHasErrors('filter');
    }

    public function test_search_by_name_and_by_phone_in_any_format(): void
    {
        [$owner, $math] = $this->owner();
        $this->accept(User::factory()->create(['name' => 'سارة أحمد', 'phone' => '+218911234567']), $math);
        $this->accept(User::factory()->create(['name' => 'عمر علي', 'phone' => '+218927654321']), $math);

        $this->actingAs($owner);
        $this->get('/teach/students?q=سارة')->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.name', 'سارة أحمد'));
        $this->get('/teach/students?q=0927654321')->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.name', 'عمر علي'));
        $this->get('/teach/students?q=٠٩١١٢٣٤٥٦٧')->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.name', 'سارة أحمد'));
        $this->get('/teach/students?q=لا+أحد')->assertInertia(fn (Assert $page) => $page->has('students.data', 0));
    }

    public function test_filter_by_course_only_accepts_the_instructors_own_courses(): void
    {
        [$owner, $math, $physics] = $this->owner();
        [, $foreign] = $this->owner();
        $this->accept(User::factory()->create(['name' => 'رياضيات']), $math);
        $this->accept(User::factory()->create(['name' => 'فيزياء']), $physics);

        $this->actingAs($owner)->get("/teach/students?course={$physics->id}")->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.name', 'فيزياء')
            ->where('course', $physics->id)
            ->has('courses', 2));

        $this->get("/teach/students?course={$foreign->id}")->assertSessionHasErrors('course');
    }

    public function test_money_counts_payments_and_leaves_voided_ones_out(): void
    {
        [$owner, $math] = $this->owner();
        $student = User::factory()->create();
        $enrollment = $this->accept($student, $math, '300.000');

        $this->payment($enrollment, $owner, '100.000');
        $this->payment($enrollment, $owner, '50.000')->forceFill(['voided_at' => now(), 'void_reason' => 'خطأ'])->save();

        $this->actingAs($owner)->get('/teach/students')->assertInertia(fn (Assert $page) => $page
            ->where('students.data.0.paid', '100')
            ->where('students.data.0.remaining', '200')
            ->where('students.data.0.enrollments.0.paid', '100')
            ->where('students.data.0.enrollments.0.remaining', '200'));
    }

    public function test_an_account_without_a_name_shows_its_phone(): void
    {
        [$owner, $math] = $this->owner();
        $nameless = User::query()->forceCreate(['phone' => '+218933333333', 'password' => 'x', 'status' => User::STATUS_ACTIVE]);
        $this->accept($nameless, $math);

        $this->actingAs($owner)->get('/teach/students')->assertInertia(fn (Assert $page) => $page
            ->where('students.data.0.has_name', false)
            ->where('students.data.0.phone', '093-3333333'));
    }

    public function test_only_instructors_can_open_it(): void
    {
        $this->get('/teach/students')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->get('/teach/students')->assertForbidden();
    }

    public function test_the_list_is_paged(): void
    {
        [$owner, $math] = $this->owner();

        for ($i = 0; $i < 17; $i++) {
            $this->accept(User::factory()->create(), $math);
        }

        $this->actingAs($owner);
        $this->get('/teach/students')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 15)
            ->where('students.last_page', 2)
            ->where('students.total', 17));
        $this->get('/teach/students?page=2')->assertInertia(fn (Assert $page) => $page->has('students.data', 2));
    }

    /**
     * @return array{0: User, 1: TeachingClass, 2: TeachingClass}
     */
    private function owner(): array
    {
        $owner = User::factory()->instructor()->create();
        $owner->instructor->forceFill(['slug' => 'teacher-'.$owner->id])->save();
        $math = TeachingClass::factory()->published()->for($owner->instructor)->create(['title' => 'رياضيات']);
        $physics = TeachingClass::factory()->published()->for($owner->instructor)->create(['title' => 'فيزياء']);

        return [$owner, $math, $physics];
    }

    private function accept(User $user, TeachingClass $class, string $fee = '300.000'): Enrollment
    {
        return Enrollment::factory()->accepted($fee)->for($class, 'teachingClass')->for($user)->create();
    }

    private function payment(Enrollment $enrollment, User $owner, string $amount): Payment
    {
        $payment = new Payment;
        $payment->forceFill([
            'enrollment_id' => $enrollment->id,
            'instructor_id' => $owner->instructor->id,
            'amount' => $amount,
            'currency_code' => 'LYD',
            'method' => 'cash',
            'paid_at' => now(),
            'receipt_number' => 'TL-'.uniqid(),
        ])->save();

        return $payment;
    }
}
