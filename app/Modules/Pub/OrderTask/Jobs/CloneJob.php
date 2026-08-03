<?php

namespace App\Modules\Pub\OrderTask\Jobs;

use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CloneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order_task;
    public $clone_contract_sub_id;
    public $clone_block_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order_task, $clone_contract_sub_id, $clone_block_id)
    {
        $this->order_task = $order_task;
        $this->clone_contract_sub_id = $clone_contract_sub_id;
        $this->clone_block_id = $clone_block_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        OrderTaskService::cloneProcess($this->order_task, $this->clone_contract_sub_id, $this->clone_block_id);
    }
}
