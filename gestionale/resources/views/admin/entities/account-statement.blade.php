@extends('admin.layouts.app')

@section('title', 'Estratto Conto - ' . $entity->full_name)

@section('content')
    <div>
        @livewire('admin.account-statement-table', [
            'entityId' => $id,
            'entity' => $entity,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statementType' => $statementType
        ])
    </div>
@endsection