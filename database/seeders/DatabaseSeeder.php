<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\InstructorApplication;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Local sample data. Every account's password is "password". Real admins are made with
 * `php artisan admin:create`; the seeded admin@tulabi.test exists only locally and sets up
 * its authenticator at first login.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->isLocal()) {
            Admin::factory()->create(['name' => 'الإدارة', 'email' => 'admin@tulabi.test']);
        }

        User::factory()->create(['name' => 'طالب تجريبي', 'phone' => '+218910000001']);
        $teacher = User::factory()->instructor()->create(['name' => 'مدرّس تجريبي', 'phone' => '+218920000002']);
        $teacher->instructor->forceFill([
            'slug' => 'demo-teacher',
            'specialty' => 'رياضيات',
            'bio' => "مدرّس رياضيات للمرحلة الثانوية منذ عشر سنوات.\nأركّز على الفهم قبل الحفظ.",
            'whatsapp' => $teacher->phone,
            'links' => ['https://facebook.com/demo', 'https://youtube.com/@demo'],
        ])->save();

        TeachingClass::factory()->published()->for($teacher->instructor)->create();
        TeachingClass::factory()->published()->online()->for($teacher->instructor)->create([
            'title' => 'لغة إنجليزية للمبتدئين',
            'price' => '150',
            'days' => [0, 2],
        ]);
        TeachingClass::factory()->for($teacher->instructor)->create(['title' => 'فيزياء (مسودة)']);

        InstructorApplication::factory()
            ->for(User::factory()->create(['name' => 'متقدّم تجريبي', 'phone' => '+218940000003']))
            ->create();
    }
}
