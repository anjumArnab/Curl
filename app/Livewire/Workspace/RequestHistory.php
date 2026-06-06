<?php

namespace App\Livewire\Workspace;

use App\Models\Project;
use App\Models\RequestHistory as RequestHistoryModel;
use Livewire\Attributes\On;
use Livewire\Component;

class RequestHistory extends Component
{
    public Project $project;

    public bool $show = false;

    public function toggle(): void
    {
        $this->show = ! $this->show;
    }

    #[On('request-executed')]
    public function refresh(): void
    {
        // Triggers a re-render so the latest request appears.
    }

    public function rerun(int $id): void
    {
        $this->dispatch('load-request', historyId: $id);
        $this->show = false;
    }

    public function clear(): void
    {
        RequestHistoryModel::whereIn('endpoint_id', $this->project->endpoints()->pluck('id'))->delete();
    }

    public function render()
    {
        $endpointIds = $this->project->endpoints()->pluck('id');

        return view('livewire.workspace.request-history', [
            'histories' => RequestHistoryModel::with('endpoint')
                ->whereIn('endpoint_id', $endpointIds)
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }
}
