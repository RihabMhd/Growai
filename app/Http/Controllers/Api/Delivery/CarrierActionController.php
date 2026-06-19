<?php 
// CarrierActionController.php
final class CarrierActionController {
    public function index(int $id, GetCarrierActionsHandler $h) {
        return ActionDefinitionResource::collection(
            $h->handle(new GetCarrierActionsQuery($id, auth()->user()->team_id))
        );
    }

    public function update(int $id, string $action, SaveActionConfigRequest $req, SaveActionConfigHandler $h) {
        $h->handle(new SaveActionConfigCommand($id, $action, auth()->user()->team_id, $req->validated()));
        return response()->json(['ok' => true]);
    }

    public function test(int $id, string $action, TestActionHandler $h) {
        return response()->json($h->handle(new TestActionCommand($id, $action, auth()->user()->team_id)));
    }

    public function registerWebhook(int $id, RegisterWebhookHandler $h) {
        return response()->json($h->handle(new RegisterWebhookCommand($id, auth()->user()->team_id)));
    }
}