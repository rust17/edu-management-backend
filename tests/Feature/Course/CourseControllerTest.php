<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\User;
use App\Models\CourseStudent;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * 测试教师可以创建课程
     */
    public function test_teacher_can_create_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $students = User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', [
                'name' => '数学课',
                'year_month' => '2024-03',
                'fee' => 100,
                'student_ids' => $students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'students' => [
                        '*' => [
                            'id',
                            'name',
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '课程创建成功',
                'data' => [
                    'name' => '数学课',
                    'year_month' => '2024-03',
                    'fee' => 100,
                    'teacher_id' => $teacher->id,
                    'students' => $students->map(fn ($student) => $student->only(['id', 'name']))->toArray()
                ]
            ]);
    }

    /**
     * 测试学生不能创建课程
     */
    public function test_student_cannot_create_course()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson('/api/courses', [
                'name' => '数学课',
                'year_month' => '2024-03',
                'fee' => 100
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您没有权限执行此操作',
                'data' => null
            ]);
    }

    /**
     * 测试课程创建验证
     */
    public function test_course_creation_validation()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => '参数校验失败: 课程名称不能为空 (and 3 more errors)'
            ])
            ->assertJsonValidationErrors(['name', 'year_month', 'fee'], 'data');
    }

    /**
     * 测试学生可以查看自己的课程
     */
    public function test_student_can_view_own_courses()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建一些课程并关联到学生
        $courses = Course::factory()->count(3)->create();
        $courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建一些不属于该学生的课程
        Course::factory()->count(2)->create();

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'year_month',
                            'fee',
                            'teacher_id',
                            'teacher' => [
                                'id',
                                'name',
                            ],
                            'invoice_status',
                            'invoice_send_at',
                            'paid_at'
                        ]
                    ],
                    'total',
                    'per_page'
                ]
            ]);
    }

    /**
     * 测试教师不能使用查看我的课程接口
     */
    public function test_teacher_cannot_view_courses_as_student()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您没有权限执行此操作',
                'data' => null
            ]);
    }

    /**
     * 测试按年月筛选课程
     */
    public function test_student_can_filter_courses_by_year_month()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建 2024-03 的课程
        $march2024Courses = Course::factory()->count(2)->create([
            'year_month' => '2024-03'
        ]);
        $march2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建 2024-04 的课程
        $april2024Courses = Course::factory()->count(3)->create([
            'year_month' => '2024-04'
        ]);
        $april2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?year_month=2024-03');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * 测试按关键词筛选课程
     */
    public function test_student_can_filter_courses_by_keyword()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建包含特定关键词的课程
        Course::factory()->count(2)->create([
            'name' => '高等数学'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建其他课程
        Course::factory()->count(3)->create([
            'name' => '英语课程'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?keyword=数学');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.name', '高等数学')
            ->assertJsonPath('data.data.1.name', '高等数学');
    }

    /**
     * 测试学生可以查看自己已选课程的详情
     */
    public function test_student_can_view_own_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'teacher' => [
                        'id',
                        'name',
                    ],
                    'invoice_status',
                    'invoice_send_at',
                    'paid_at'
                ]
            ]);
    }

    /**
     * 测试学生不能查看未选课程的详情
     */
    public function test_student_cannot_view_unselected_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        // 不关联学生和课程

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您没有权限查看该课程',
                'data' => null
            ]);
    }

    /**
     * 测试教师可以获取自己的课程
     */
    public function test_teacher_can_get_their_courses()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 为该教师创建3个课程
        $teacherCourses = Course::factory()->count(3)->create([
            'teacher_id' => $teacher->id
        ]);

        // 创建另一个教师和他的课程，用于验证不会获取到其他教师的课程
        Course::factory()->count(2)->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])
        ]);

        // 模拟教师登录并请求课程列表
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses');

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'year_month',
                            'teacher_id',
                            'fee',
                            'students',
                        ]
                    ]
                ]
            ])
            // 验证返回的课程确实属于该教师
            ->assertJson(
                fn (AssertableJson $json) => $json->has('data.data',
                    fn (AssertableJson $json) => $json->each(
                        fn (AssertableJson $json) => $json->where('teacher_id', $teacher->id)->etc()
                    )
                )->etc()
            );
    }

    /**
     * 测试老师按关键词筛选课程
     */
    public function test_teacher_can_filter_courses_by_keyword()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建测试课程数据
        collect(['PHP课程', 'Python课程', 'Java课程'])->each(
            fn ($name) => Course::factory()->create([
                'teacher_id' => $teacher->id,
                'name' => $name
            ])
        );

        // 测试关键词筛选
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses?keyword=PHP');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'PHP课程');
    }

    /**
     * 测试老师按年月筛选课程
     */
    public function test_teacher_can_filter_courses_by_year_month()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建不同月份的课程
        collect(['2024-01', '2024-02', '2024-03'])->each(
            fn ($yearMonth) => Course::factory()->create([
                'teacher_id' => $teacher->id,
                'year_month' => $yearMonth
            ])
        );

        // 测试年月筛选
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses?year_month=2024-02');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.year_month', '2024-02');
    }

    /**
     * 测试教师可以查看自己课程的详情
     */
    public function test_teacher_can_view_own_course_detail()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建一个课程并关联学生和发票
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => '测试课程',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // 创建学生并关联到课程
        $students = User::factory()->count(3)->create(['role' => 'student']);
        $course->students()->attach($students->pluck('id'));

        // 为其中一个学生创建发票
        Invoice::factory()->create([
            'course_id' => $course->id,
            'student_id' => $students->first()->id,
            'status' => 'pending'
        ]);

        // 请求课程详情
        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/courses/teacher-courses/{$course->id}");

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'students' => [
                        '*' => [
                            'id',
                            'name',
                            'invoice_status',
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'id' => $course->id,
                    'name' => '测试课程',
                    'year_month' => '2024-03',
                    'fee' => 100,
                    'teacher_id' => $teacher->id,
                    'students' => [
                        [
                            'id' => $students[0]->id,
                            'name' => $students[0]->name,
                            'invoice_status' => 'pending'
                        ]
                    ]
                ]
            ]);

        // 验证返回的学生数据
        $this->assertCount(3, $response->json('data.students'));

        // 验证第一个学生有发票状态
        $this->assertEquals('pending', $response->json('data.students.0.invoice_status'));

        // 验证其他学生没有发票状态
        $this->assertNull($response->json('data.students.1.invoice_status'));
        $this->assertNull($response->json('data.students.2.invoice_status'));
    }

    /**
     * 测试教师不能查看其他教师的课程详情
     */
    public function test_teacher_cannot_view_other_teachers_course_detail()
    {
        // 创建两个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建属于其他教师的课程
        $course = Course::factory()->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])
        ]);

        // 尝试查看其他教师的课程详情
        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/courses/teacher-courses/{$course->id}");

        // 验证响应
        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您只能查看自己的课程',
                'data' => null
            ]);
    }

    /**
     * 测试教师可以编辑自己的课程
     */
    public function test_teacher_can_update_own_course()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建一个课程
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => '原始课程名',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // 创建一些新学生
        $newStudents = User::factory()->count(2)->create(['role' => 'student']);

        // 请求更新课程
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => '新课程名',
                'year_month' => '2024-04',
                'fee' => 200,
                'student_ids' => $newStudents->pluck('id')->toArray()
            ]);

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'students' => [
                        '*' => [
                            'id',
                            'name'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '课程更新成功',
                'data' => [
                    'id' => $course->id,
                    'name' => '新课程名',
                    'year_month' => '2024-04',
                    'fee' => 200,
                    'teacher_id' => $teacher->id
                ]
            ]);

        // 验证数据库更新
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => '新课程名',
            'year_month' => '2024-04-01 00:00:00',
            'fee' => 200
        ]);

        // 验证学生关联关系
        $this->assertEquals(
            $newStudents->pluck('id')->toArray(),
            $course->fresh()->students->pluck('id')->toArray()
        );
    }

    /**
     * 测试教师不能编辑其他教师的课程
     */
    public function test_teacher_cannot_update_other_teachers_course()
    {
        // 创建两个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);

        // 创建属于其他教师的课程
        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'name' => '原始课程名',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // 尝试更新其他教师的课程
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => '新课程名',
                'year_month' => '2024-04',
                'fee' => 200
            ]);

        // 验证响应
        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您只能编辑自己的课程',
                'data' => null
            ]);

        // 验证数据库未更新
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => '原始课程名',
            'year_month' => '2024-03-01 00:00:00',
            'fee' => 100
        ]);
    }

    /**
     * 测试课程编辑验证
     */
    public function test_course_update_validation()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建一个课程
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        // 测试空数据
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => '参数校验失败: 课程名称不能为空 (and 2 more errors)'
            ])
            ->assertJsonValidationErrors(['name', 'year_month', 'fee'], 'data');

        // 测试无效的年月格式
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => '新课程名',
                'year_month' => 'invalid-date',
                'fee' => 200
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year_month'], 'data');

        // 测试无效的学生ID
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => '新课程名',
                'year_month' => '2024-04',
                'fee' => 200,
                'student_ids' => [999999] // 不存在的学生ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_ids.0'], 'data');
    }
}
