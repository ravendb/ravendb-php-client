<?php

namespace RavenDB\Primitives;

class Timer implements CleanCloseable
{
//  private final ExecutorService executorService;
//    private final Runnable action;
//    private ScheduledFuture<Void> scheduledFuture;
//    private Duration period;
//
//    public Timer(Runnable action, Duration dueTime, ExecutorService executorService) {
//        this(action, dueTime, null, executorService);
//    }
//
//    public Timer(Runnable action, Duration dueTime, Duration period, ExecutorService executorService) {
//        this.executorService = executorService;
//        this.action = action;
//        this.period = period;
//        schedule(dueTime);
//    }
//
//    public void change(Duration dueTime) {
//        change(dueTime, null);
//    }
//
//    public void change(Duration dueTime, Duration period) {
//        this.period = period;
//        this.scheduledFuture.cancel(false);
//        this.schedule(dueTime);
//    }
//
//    private void schedule(Duration dueTime) {
//        this.scheduledFuture = TimerService.service.schedule(() -> {
//            CompletableFuture.runAsync(() -> {
//                if (period != null) {
//                    schedule(period);
//                }
//                this.action.run();
//            }, executorService);
//            return null;
//        }, dueTime.toMillis(), TimeUnit.MILLISECONDS);
//    }
//
    public function close(): void
    {
//        if (this.scheduledFuture != null) {
//            this.scheduledFuture.cancel(false);
//        }
    }
}
