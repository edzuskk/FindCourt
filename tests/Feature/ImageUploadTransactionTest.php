<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImageUploadTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ImageUploadTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_database_write_removes_new_image_and_keeps_previous_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['photo' => 'profiles/old.png']);
        Storage::disk('public')->put('profiles/old.png', 'old image');
        $newPath = '';

        try {
            app(ImageUploadTransaction::class)->persist(
                UploadedFile::fake()->createWithContent(
                    'new.png',
                    file_get_contents(public_path('images/basketball-marker.png'))
                ),
                'profiles',
                function (?string $path) use ($user, &$newPath): void {
                    $newPath = $path;
                    $user->update(['photo' => $path]);

                    throw new RuntimeException('Simulated persistence failure.');
                },
                $user->photo
            );

            $this->fail('The persistence exception should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated persistence failure.', $exception->getMessage());
        }

        $this->assertNotSame('', $newPath);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'photo' => 'profiles/old.png',
        ]);
        Storage::disk('public')->assertExists('profiles/old.png');
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_failed_database_delete_keeps_stored_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('courts/court.png', 'court image');

        try {
            app(ImageUploadTransaction::class)->deleteAfter(
                function (): void {
                    throw new RuntimeException('Simulated deletion failure.');
                },
                ['courts/court.png']
            );

            $this->fail('The database exception should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated deletion failure.', $exception->getMessage());
        }

        Storage::disk('public')->assertExists('courts/court.png');
    }

    public function test_failed_image_write_keeps_previous_image_and_skips_database_write(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['photo' => 'profiles/old.png']);
        Storage::disk('public')->put('profiles/old.png', 'old image');
        $upload = \Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('store')
            ->once()
            ->with('profiles', 'public')
            ->andReturn(false);

        try {
            app(ImageUploadTransaction::class)->persist(
                $upload,
                'profiles',
                fn () => $this->fail('The database operation must not run after a failed file write.'),
                $user->photo
            );

            $this->fail('A failed image write should be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to store uploaded image.', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'photo' => 'profiles/old.png',
        ]);
        Storage::disk('public')->assertExists('profiles/old.png');
    }
}
