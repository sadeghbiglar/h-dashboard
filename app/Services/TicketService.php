<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

class TicketService
{
    /**
     * یک تیکت فقط وقتی رد می‌شود که هنوز بسته نشده باشد —
     * یک تیکت completed یا rejected دیگر قابل رد شدن نیست.
     */
    public static function canReject(Ticket $ticket): bool
    {
        return ! in_array($ticket->status, ['completed', 'rejected'], true);
    }

    /**
     * وقتی همه‌ی تیکت‌های یک Todo بسته شدند، آن Todo را هم کامل کن.
     * قبلاً همین منطق در دو مسیر single و bulk کپی شده بود (#378/#389/#531 تکرار).
     */
    public static function syncParentTaskCompletion(int $taskId): void
    {
        $open = Ticket::query()
            ->where('task_id', $taskId)
            ->where('status', '!=', 'completed')
            ->count();

        if ($open === 0) {
            Todo::query()->where('id', $taskId)->update(['is_completed' => true]);
        }
    }

    /**
     * وظیفه‌ی مرتبط با تیکت، فقط وقتی در اسکوپ کاربر باشد —
     * #847: لینکِ todo به یک واحدِ خارج از scope نباید عنوان/تاریخ آن را لو بدهد.
     * یک todo بدون unit برای صاحب خودش در اسکوپ است (#838 contract).
     *
     * @param  array<int>  $accessibleIds
     */
    public static function taskIfInScope(?int $taskId, array $accessibleIds): ?Todo
    {
        if (! $taskId) {
            return null;
        }

        return Todo::query()
            ->whereKey($taskId)
            ->where(fn ($q) => $q->whereIn('unit_id', $accessibleIds)
                ->orWhere(fn ($q) => $q->whereNull('unit_id')->where('user_id', Auth::id())))
            ->first();
    }
}
