<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'subject,topic,body,explanation,difficulty,tags,choice_a,choice_b,choice_c,choice_d,correct';

    private const VALID_ROW = 'Verbal Ability,Analogies,Pick the synonym of brisk,,easy,vocabulary,Quick,Slow,,,A';

    private function topic(): Topic
    {
        $subject = Subject::factory()->create(['name' => 'Verbal Ability']);

        return Topic::factory()->create([
            'subject_id' => $subject->id,
            'name' => 'Analogies',
        ]);
    }

    /** @return array<string, array<string, string> */
    private function staged(string ...$rows): array
    {
        Storage::fake('local');
        Storage::disk('local')->put('imports/test.csv', self::HEADER."\n".implode("\n", $rows));

        return [
            'question_import' => [
                'path' => 'imports/test.csv',
                'name' => 'questions.csv',
            ],
        ];
    }

    public function test_upload_is_staged_for_preview(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'instructor']);
        $file = UploadedFile::fake()->createWithContent('questions.csv', self::HEADER."\n".self::VALID_ROW);

        $this->actingAs($user)
            ->post('/questions/import/preview', ['file' => $file])
            ->assertRedirect('/questions/import')
            ->assertSessionHas('question_import');
    }

    public function test_valid_file_imports_draft_questions(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topic = $this->topic();

        $this->actingAs($user)
            ->withSession($this->staged(self::VALID_ROW))
            ->post('/questions/import')
            ->assertRedirect('/questions/import')
            ->assertSessionHasNoErrors();

        $question = Question::where('topic_id', $topic->id)->firstOrFail();

        $this->assertSame('draft', $question->status->value);
        $this->assertSame(2, $question->choices()->count());
        $this->assertTrue(Choice::where('body', 'Quick')->firstOrFail()->is_correct);
        $this->assertDatabaseHas('tags', ['slug' => 'vocabulary']);
    }

    public function test_file_with_an_error_imports_nothing(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $this->topic();
        $bad = 'Verbal Ability,No Such Topic,Another question,,easy,,A1,B1,,,A';

        $this->actingAs($user)
            ->withSession($this->staged(self::VALID_ROW, $bad))
            ->post('/questions/import')
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_duplicate_of_existing_question_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $topic = $this->topic();
        Question::factory()->create([
            'topic_id' => $topic->id,
            'body' => 'Pick the synonym of brisk',
        ]);

        $this->actingAs($user)
            ->withSession($this->staged(self::VALID_ROW))
            ->post('/questions/import')
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('questions', 1);
    }

    public function test_examinee_cannot_open_import_page(): void
    {
        $user = User::factory()->create(['role' => 'examinee']);

        $this->actingAs($user)->get('/questions/import')->assertForbidden();
    }
}
