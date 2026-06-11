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
    public function __construct(private readonly GetDashboardHandler $handler) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:today,yesterday,last_7_days,this_month',
        ]);

        /** @var \App\Domain\Teams\Models\User $user */
        $user = Auth::user();

        $result = $this->handler->handle(new GetDashboardQuery(
            period: $request->input('period', 'today'),
            userId: $user->id,
            teamId: $user->team_id,
            isAgent: $user->isAgent(),
        ));

        return response()->json($result);
    }
}
