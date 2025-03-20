<div class="px-4 py-2">
    <div class="flex flex-col gap-1">
        @foreach($getRecord()->details as $detail)
            <div class="flex items-center gap-2">
                <span class="font-medium">{{ $detail->product_name }}</span>
                <span class="text-gray-500 text-sm">({{ $detail->quantity }} unid.)</span>
            </div>
        @endforeach
    </div>
</div>
