<ul>
    @foreach ($nodes as $node)
        <li>
            @if ($node['children'])
                <details open>
                    <summary>{{ $node['name'] }}/</summary>
                    @include('flow.tree', ['nodes' => $node['children']])
                </details>
            @else
                <button type="button" class="tree-file" data-file="{{ $node['id'] }}" title="{{ $files[$node['id']]['path'] }}">{{ $node['name'] }}</button>
            @endif
        </li>
    @endforeach
</ul>
