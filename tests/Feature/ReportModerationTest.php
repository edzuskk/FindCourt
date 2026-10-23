<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\CourtReport;
use App\Models\CourtReview;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_paginates_users_without_changing_total_counts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(26)->create();

        $response = $this->actingAs($admin)->get('/admin?usersPage=2');

        $response->assertOk();
        $this->assertSame(2, $response->viewData('users')->count());
        $response->assertViewHas('userCount', 27);
        $response->assertSee('Page 2');
    }

    public function test_admin_resolution_records_actor_and_timestamp_for_both_report_types(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $reporter->id,
            'username' => $reporter->username,
            'rating' => 4,
        ]);
        $courtReport = CourtReport::create([
            'court_id' => $court->id,
            'user_id' => $reporter->id,
            'reportReason' => 'unsafe',
        ]);
        $reviewReport = ReviewReport::create([
            'review_id' => $review->id,
            'user_id' => $reporter->id,
            'reportReason' => 'spam',
        ]);

        $this->actingAs($reporter)
            ->patchJson("/admin/reports/{$courtReport->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson("/admin/reports/{$courtReport->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($admin)
            ->patchJson("/admin/review-reports/{$reviewReport->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ([$courtReport, $reviewReport] as $report) {
            $report->refresh();
            $this->assertTrue($report->is_resolved);
            $this->assertSame($admin->id, $report->resolved_by);
            $this->assertNotNull($report->resolved_at);
            $this->assertSame($admin->id, $report->resolvedBy->id);
        }

        $initialResolutions = [
            $courtReport->fresh()->resolved_at->toDateTimeString(),
            $reviewReport->fresh()->resolved_at->toDateTimeString(),
        ];
        $secondAdmin = User::factory()->create(['is_admin' => true]);
        $this->travel(1)->day();

        $this->actingAs($secondAdmin)
            ->patchJson("/admin/reports/{$courtReport->id}")
            ->assertOk();
        $this->actingAs($secondAdmin)
            ->patchJson("/admin/review-reports/{$reviewReport->id}")
            ->assertOk();

        $this->assertSame($admin->id, $courtReport->fresh()->resolved_by);
        $this->assertSame($admin->id, $reviewReport->fresh()->resolved_by);
        $this->assertSame($initialResolutions[0], $courtReport->fresh()->resolved_at->toDateTimeString());
        $this->assertSame($initialResolutions[1], $reviewReport->fresh()->resolved_at->toDateTimeString());

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Resolved by')
            ->assertSee($admin->username)
            ->assertSee($courtReport->fresh()->resolved_at->format('M d, Y H:i'));
    }

    public function test_reporters_cannot_create_duplicate_court_or_review_reports(): void
    {
        $reporter = User::factory()->create();
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => User::factory()->create()->id,
            'username' => 'Reviewer',
            'rating' => 3,
        ]);

        $this->actingAs($reporter)
            ->postJson("/courts/{$court->id}/report", ['reportReason' => 'unsafe'])
            ->assertOk();
        $this->actingAs($reporter)
            ->postJson("/courts/{$court->id}/report", ['reportReason' => 'unsafe'])
            ->assertUnprocessable();

        $this->actingAs($reporter)
            ->postJson("/reviews/{$review->id}/report", ['reportReason' => 'spam'])
            ->assertOk();
        $this->actingAs($reporter)
            ->postJson("/reviews/{$review->id}/report", ['reportReason' => 'spam'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('court_reports', 1);
        $this->assertDatabaseCount('review_reports', 1);
    }

    public function test_review_report_displays_the_reviewers_current_username(): void
    {
        $reviewer = User::factory()->create(['username' => 'OriginalName']);
        $reporter = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $court = Court::create([
            'name' => 'Central Court',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
        ]);
        $review = CourtReview::create([
            'court_id' => $court->id,
            'user_id' => $reviewer->id,
            'username' => 'OriginalName',
            'rating' => 4,
        ]);
        ReviewReport::create([
            'review_id' => $review->id,
            'user_id' => $reporter->id,
            'reportReason' => 'spam',
        ]);
        $reviewer->update(['username' => 'CurrentName']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('CurrentName')
            ->assertDontSee('OriginalName');
    }
}
