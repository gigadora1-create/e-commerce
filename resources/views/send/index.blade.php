
@extends('layouts.app')

@section('contents')
    <div class="container py-4 position-relative">
        <div class="text-center mb-4">
            <h1 class="display-5">Mensajes</h1>
        </div>

        <!-- Formulario de búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="d-flex flex-wrap gap-2" action="{{ route('send.index') }}" method="GET">
                    <div class="input-group flex-grow-1">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Buscar mensajes" aria-label="Buscar mensajes" value="{{ request('search') }}">
                    </div>
                    <div>
                        <input type="date" id="start_date" name="start_date" class="form-control" placeholder="Fecha de inicio" aria-label="Fecha de inicio" value="{{ request('start_date') }}">
                    </div>
                    <div>
                        <input type="date" id="end_date" name="end_date" class="form-control" placeholder="Fecha de fin" aria-label="Fecha de fin" value="{{ request('end_date') }}">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Buscar</button>
                    <button type="reset" class="btn btn-secondary" onclick="limpiarCampos()"><i class="fas fa-eraser me-2"></i>Limpiar</button>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#statsModal"><i class="fas fa-chart-bar me-2"></i>Estadísticas</button>
                </form>
            </div>
        </div>

        <!-- Botón para cargar mensajes -->
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('send-sms') }}" class="btn btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Cargar Mensajes">
                <i class="fas fa-upload me-2"></i>Cargar Mensajes
            </a>
        </div>

        <!-- Modal para estadísticas -->
        <div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="statsModalLabel">Estadísticas de Mensajes</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Mensajes Enviados por Día</h6>
                                <div id="dailyMessagesChart"></div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Mensajes Enviados Mensuales por Cliente</h6>
                                <div id="monthlyClientMessagesChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de mensajes -->
        <div class="card">
            <div class="card-body">
                <div class="scroll-horizontal">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Número de Teléfono</th>
                                <th>Mensaje</th>
                                <th>Estado</th>
                                <th>Fecha de Envío</th>
                                <th>Usuario</th>
                                <th>Fecha de Creación</th>
                                <th>Fecha de Actualización</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($logs->count() > 0)
                                @foreach ($logs as $log)
                                    <tr>
                                        <td>{{ $log->phone_number }}</td>
                                        <td>{{ $log->message }}</td>
                                        <td>{{ $log->status }}</td>
                                        <td>{{ $log->sent_at }}</td>
                                        <td class="align-middle">{{ $log->user?->name ?? 'Sin usuario' }}</td>
                                        <td>{{ $log->created_at }}</td>
                                        <td>{{ $log->updated_at }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="text-center" colspan="7">No se encontraron mensajes</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-center mt-4">
            {{ $logs->appends(request()->query())->links('pagination.custom') }}
        </div>

       

        <style>
            body {
                background-color: #f4f6f9;
                font-family: 'Inter', sans-serif;
            }
            h1, h2 {
                color: #1a1a1a;
                font-weight: 700;
            }
            .table thead th {
                background-color: #dc3545 !important;
                color: white !important;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .table {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .table tbody tr {
                transition: background-color 0.2s ease;
            }
            .table tbody tr:hover {
                background-color: #f8f9fa;
            }
            .btn {
                border-radius: 6px;
                padding: 8px 16px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }
            .modal-content {
                border-radius: 12px;
                border: none;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            .modal-header {
                border-bottom: none;
                padding: 1.5rem;
                background: linear-gradient(90deg, #007bff, #0056b3);
            }
            .modal-title {
                font-weight: 600;
                color: white;
            }
            .scroll-horizontal {
                overflow-x: auto;
                max-height: 500px;
                border-radius: 8px;
            }
            .card {
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .table th, .table td {
                white-space: nowrap;
                vertical-align: middle;
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            function limpiarCampos() {
                document.querySelector('input[name="search"]').value = '';
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
            }

            function renderCharts() {
                var dailyMessagesOptions = {
                    chart: {
                        type: 'line',
                        height: 350,
                        foreColor: '#1a1a1a',
                        background: 'transparent'
                    },
                    series: [{
                        name: 'Mensajes Enviados',
                        data: {!! json_encode(array_column($dailyMessages, 'total')) !!}
                    }],
                    xaxis: {
                        categories: {!! json_encode(array_column($dailyMessages, 'date')) !!},
                        labels: {
                            style: {
                                colors: '#1a1a1a'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#1a1a1a'
                            }
                        }
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    colors: ['#007bff'],
                    grid: {
                        borderColor: '#e0e0e0'
                    }
                };

                var dailyMessagesChart = new ApexCharts(document.querySelector("#dailyMessagesChart"), dailyMessagesOptions);
                dailyMessagesChart.render();

                var monthlyClientMessagesOptions = {
                    chart: {
                        type: 'bar',
                        height: 350,
                        foreColor: '#1a1a1a',
                        background: 'transparent'
                    },
                    series: [
                        @foreach ($monthlyClientMessages as $client => $data)
                        {
                            name: '{{ $client }}',
                            data: {!! json_encode(array_values($data)) !!},
                        },
                        @endforeach
                    ],
                    xaxis: {
                        categories: {!! json_encode($months) !!},
                        labels: {
                            style: {
                                colors: '#1a1a1a'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#1a1a1a'
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        }
                    },
                    colors: ['#007bff', '#dc3545', '#28a745', '#6c757d'],
                    grid: {
                        borderColor: '#e0e0e0'
                    },
                    legend: {
                        labels: {
                            colors: '#1a1a1a'
                        }
                    }
                };

                var monthlyClientMessagesChart = new ApexCharts(document.querySelector("#monthlyClientMessagesChart"), monthlyClientMessagesOptions);
                monthlyClientMessagesChart.render();
            }

            document.getElementById('statsModal').addEventListener('shown.bs.modal', function () {
                renderCharts();
            });

            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '{{ session('success') }}',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    timer: 5000,
                    showConfirmButton: true
                });
            @endif
        </script>
    </div>
@endsection
