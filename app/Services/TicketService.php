<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Todo;

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
}
