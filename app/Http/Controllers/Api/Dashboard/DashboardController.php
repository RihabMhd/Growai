<?php
// Http/Controllers/Api/Dashboard/DashboardController.php
namespace App\Http\Controllers\Api\Dashboard;

use App\Application\Dashboard\GetDashboard\GetDashboardHandler;
use App\Application\Dashboard\GetDashboard\GetDashboardQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardHandler $handler
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:all_time,today,yesterday,last_7_days,last_30_days,this_month,last_month,last_90_days',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);
        /** @var \App\Domain\Teams\Models\User $user */
        $user = Auth::user();

        $result = $this->handler->handle(new GetDashboardQuery(
            period: $request->filled('from')
                ? 'custom'
                : $request->input('period', 'today'),

            userId: $user->id,

            teamId: $user->team_id,

            isAgent: $user->isAgent(),

            from: $request->input('from'),

            to: $request->input('to'),
        ));

        return response()->json([
            ...$result,
            'shops' => collect($result['shops'])->values(),
        ]);
    }
}
