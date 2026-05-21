<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\TeamInvitationMail;

class TeamController extends Controller
{
    /**
     * Get the team settings and all member configurations.
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // 1. Get or create a default team
        $team = Team::first();
        if (!$team) {
            $team = Team::create([
                'dispatch_auto' => false,
                'inactive_strategy' => 'do_nothing',
                'commission_currency' => 'DZ DA'
            ]);
        }

        // 2. Ensure all existing users belong to this active team for ease of use
        User::where('team_id', '!=', $team->id)->orWhereNull('team_id')->update(['team_id' => $team->id]);

        // 3. Load users with their assigned products
        $members = User::where('team_id', $team->id)->with('products')->get();

        // 4. Map 'staff' role to 'agent' in the response to align with frontend
        $members->map(function ($member) {
            $member->role_display = $member->role === 'staff' ? 'agent' : 'admin';
            return $member;
        });

        // 5. Fetch all database products for selection
        $allProducts = \App\Models\Product::all();

        return response()->json([
            'team' => $team,
            'members' => $members,
            'products' => $allProducts
        ]);
    }

    /**
     * Add a new member to the team and send an invitation email.
     */
    public function storeMember(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,agent,staff',
            'avatar' => 'nullable|string'
        ]);

        // Find or create default team
        $team = Team::first();
        if (!$team) {
            $team = Team::create([
                'dispatch_auto' => false,
                'inactive_strategy' => 'do_nothing',
                'commission_currency' => 'DZ DA'
            ]);
        }

        // Extract name from email (e.g. john.doe@example.com -> John Doe)
        $emailParts = explode('@', $validated['email']);
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $emailParts[0]));

        // Map 'agent' to 'staff' in DB
        $dbRole = ($validated['role'] === 'agent' || $validated['role'] === 'staff') ? 'staff' : 'admin';

        // Generate a friendly, secure temporary password
        $tempPassword = 'Growai@' . Str::upper(Str::random(6)) . '!';

        $user = User::create([
            'team_id' => $team->id,
            'name' => $name,
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'role' => $dbRole,
            'is_active' => true,
            'quota' => 1,
            'is_dispatch_active' => true,
            'commission_trigger' => 'none',
            'commission_amount' => 0.00,
            'commission_type' => 'fixed',
            'avatar' => $validated['avatar'] ?? null
        ]);

        $user->role_display = $user->role === 'staff' ? 'agent' : 'admin';
        $user->load('products');

        // Send email
        $roleDisplay = $dbRole === 'staff' ? 'Agent' : 'Admin';
        $loginUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $emailSent = false;

        try {
            Mail::to($validated['email'])->send(new TeamInvitationMail(
                $name,
                $validated['email'],
                $tempPassword,
                $roleDisplay,
                $loginUrl
            ));
            $emailSent = true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('SMTP Mail sending failed: ' . $e->getMessage());
        }

        $message = $emailSent
            ? "Membre ajouté avec succès ! Un e-mail d'invitation avec ses identifiants a été envoyé à " . $validated['email'] . "."
            : "Membre ajouté avec succès ! E-mail d'invitation simulé car le SMTP n'est pas configuré. Mot de passe temporaire : " . $tempPassword;

        return response()->json([
            'message' => $message,
            'member' => $user,
            'success' => true
        ], 201);
    }

    /**
     * Update a team member's configuration (roles, active, quotas, commissions, products).
     */
    public function updateMember(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'role' => 'nullable|in:admin,agent,staff',
            'is_active' => 'nullable|boolean',
            'quota' => 'nullable|integer|min:0',
            'is_dispatch_active' => 'nullable|boolean',
            'commission_trigger' => 'nullable|string',
            'commission_amount' => 'nullable|numeric|min:0',
            'commission_type' => 'nullable|string|in:fixed,percent',
            'avatar' => 'nullable|string',
            'product_ids' => 'nullable|array'
        ]);

        // Handle role mapping if provided
        if (isset($validated['role'])) {
            $validated['role'] = ($validated['role'] === 'agent' || $validated['role'] === 'staff') ? 'staff' : 'admin';
        }

        $user->update($validated);

        // Sync assigned products if provided
        if (isset($validated['product_ids'])) {
            $user->products()->sync($validated['product_ids']);
        }

        $user->load('products');
        $user->role_display = $user->role === 'staff' ? 'agent' : 'admin';

        return response()->json([
            'message' => 'Configuration du membre mise à jour avec succès.',
            'member' => $user
        ]);
    }

    /**
     * Log in as a specific user (impersonation).
     */
    public function impersonate(Request $request, $id)
    {
        // 1. Ensure caller is admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can impersonate users.'
            ], 403);
        }

        // 2. Find target user
        $targetUser = User::findOrFail($id);

        // 3. Generate token
        $token = $targetUser->createToken('impersonated_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion simulée réussie en tant que ' . $targetUser->name,
            'token' => $token,
            'user' => $targetUser
        ]);
    }

    /**
     * Remove a member from the team.
     */
    public function destroyMember(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $user = User::findOrFail($id);

        // Protect last admin from deleting themselves
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'Impossible de supprimer le dernier administrateur !'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Membre supprimé avec succès.'
        ]);
    }

    /**
     * GET /api/team/settings
     * Returns the current team settings the frontend needs.
     */
    public function settings()
    {
        $team = Team::first();

        return response()->json([
            'whatsapp_language' => $team?->whatsapp_language ?? 'FR',
        ]);
    }

    /**
     * POST /api/team/settings
     * Body: { "whatsapp_language": "FR" | "AR" | "FR/AR" | "Darija AR" | "Darija FR" }
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_language' => ['required', 'string', 'in:FR,AR,FR/AR,Darija AR,Darija FR'],
        ]);

        $team = Team::first();

        if (!$team) {
            return response()->json(['message' => 'No team found.'], 404);
        }

        $team->whatsapp_language = $validated['whatsapp_language'];
        $team->save();

        return response()->json([
            'success'            => true,
            'whatsapp_language'  => $team->whatsapp_language,
        ]);
    }
}
