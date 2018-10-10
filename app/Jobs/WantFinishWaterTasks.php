<?php

namespace App\Jobs;

use App\Services\WaterService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class WantFinishWaterTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $waters;
    private $waterService;
    public function __construct(array $waters,WaterService $waterService)
    {
        $this->waters=$waters;
        $this->waterService=$waterService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->waters as $key =>$value){
            $this->waterService->finishOrder($value);
        }
    }
}
