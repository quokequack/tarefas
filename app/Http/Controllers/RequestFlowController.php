<?php

namespace App\Http\Controllers;

use App\Services\RequestTrace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestFlowController extends Controller
{
    public function __construct(private RequestTrace $trace) {}

    public function index(): View
    {
        $tree = [];
        $files = RequestTrace::files();
        foreach ($files as $id => $file) {
            $branch = &$tree;
            $parts = explode('/', $file['path']);
            foreach ($parts as $index => $name) {
                $branch[$name] ??= ['name' => $name, 'children' => [], 'id' => null];
                if ($index === count($parts) - 1) {
                    $branch[$name]['id'] = $id;
                }
                $branch = &$branch[$name]['children'];
            }
            unset($branch);
        }

        return view('flow.index', ['files' => $files, 'tree' => $tree]);
    }

    public function events(Request $request): JsonResponse
    {
        return response()->json(['requests' => $this->trace->recent($request)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function source(Request $request, string $file): JsonResponse
    {
        $data = $request->validate(['method' => ['nullable', 'string', 'max:100']]);

        return response()->json($this->trace->source($file, $data['method'] ?? null))
            ->header('Cache-Control', 'no-store, private');
    }
}
