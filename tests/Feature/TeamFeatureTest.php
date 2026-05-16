<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\User;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can invite/add a member and receive email.
     */
    public function test_admin_can_invite_member_and_email_is_sent(): void
    {
        Mail::fake();

        // 1. Create admin user
        $admin = User::factory()->admin()->create();

        // 2. Add member via API
        $response = $this->actingAs($admin)
                         ->postJson('/api/auth/team/members', [
                             'email' => 'new.agent@growai.com',
                             'role' => 'agent'
                         ]);

        // 3. Assert creation success
        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'member']);
        
        $this->assertDatabaseHas('users', [
            'email' => 'new.agent@growai.com',
            'role' => 'staff'
        ]);

        // 4. Assert email was sent
        Mail::assertSent(TeamInvitationMail::class, function ($mail) {
            return $mail->hasTo('new.agent@growai.com') && 
                   $mail->role === 'Agent' &&
                   str_contains($mail->password, 'Growai@');
        });
    }

    /**
     * Test non-admin (agent) is unauthorized to access team settings.
     */
    public function test_agent_is_unauthorized_to_access_team_endpoints(): void
    {
        // 1. Create agent user
        $agent = User::factory()->create(['role' => 'staff']);

        // 2. Attempt getting team index
        $response = $this->actingAs($agent)->getJson('/api/auth/team');
        $response->assertStatus(403);

        // 3. Attempt adding member
        $responseAdd = $this->actingAs($agent)->postJson('/api/auth/team/members', [
            'email' => 'hacker@growai.com',
            'role' => 'admin'
        ]);
        $responseAdd->assertStatus(403);
    }
}
