<x-filament::form>
    <x-filament-forms::field-wrapper>
        <x-filament-forms::select
            name="format"
            label="Export Format"
            :options="[
                'excel' => 'Excel',
                'pdf' => 'PDF',
                'csv' => 'CSV',
            ]"
            required
        />
    </x-filament-forms::field-wrapper>

    <x-filament-forms::field-wrapper>
        <x-filament-forms::checkbox-list
            name="columns"
            label="Select Columns"
            :options="[
                'id' => 'ID',
                'title' => 'Title',
                'content' => 'Content',
                'status' => 'Status',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
            ]"
            required
        />
    </x-filament-forms::field-wrapper>
</x-filament::form>