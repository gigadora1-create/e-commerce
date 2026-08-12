<div class="warehouse-board">
    @if(!empty($locationBoards))
        @foreach($locationBoards as $warehouseGroup)
            <section class="warehouse-board__group" data-warehouse-group>
                <div class="warehouse-board__header">
                    <div>
                        <div class="warehouse-board__eyebrow">Bodega</div>
                        <h5 class="warehouse-board__title">{{ $warehouseGroup['warehouse'] }}</h5>
                        <div class="warehouse-board__subtitle">
                            {{ number_format($warehouseGroup['locations_count'] ?? 0) }} ubicaciones ·
                            {{ number_format($warehouseGroup['guides_count'] ?? 0) }} guías activas
                        </div>
                    </div>
                    <div class="warehouse-board__badges">
                        <span class="badge bg-light text-dark">{{ number_format($warehouseGroup['locations_count'] ?? 0) }} ubicaciones</span>
                        <span class="badge bg-dark">{{ number_format($warehouseGroup['guides_count'] ?? 0) }} guías activas</span>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach($warehouseGroup['locations'] as $location)
                        <div
                            class="col-md-6 col-xl-4"
                            data-location-card-wrapper
                            data-location-search="{{ mb_strtolower(trim(($location['code'] ?? '') . ' ' . ($location['name'] ?? '') . ' ' . ($location['warehouse'] ?? '') . ' ' . ($location['description'] ?? '') . ' ' . collect($location['guides'] ?? [])->pluck('guide')->filter()->implode(' ') . ' ' . ($location['is_storage'] ? 'almacenamiento' : 'normal'))) }}"
                        >
                            @php
                                $locationGuides = collect($location['guides'] ?? []);
                                $previewGuides = $locationGuides->take(2);
                                $remainingGuides = max(0, $locationGuides->count() - $previewGuides->count());
                            @endphp

                            <div class="location-card {{ $location['is_storage'] ? 'location-card--storage' : '' }}">
                                <div class="location-card__header">
                                    <div>
                                        <h5 class="location-card__title">{{ $location['code'] }}</h5>
                                        <div class="location-card__subtitle">{{ $location['warehouse'] }}</div>
                                    </div>
                                    @if($location['is_storage'])
                                        <span class="badge bg-warning text-dark">Almacenamiento</span>
                                    @else
                                        <span class="badge bg-success">Activa</span>
                                    @endif
                                </div>

                                <div class="location-card__body">
                                    <div class="fw-bold mb-1">{{ $location['name'] }}</div>
                                    <div class="text-muted small mb-3">{{ $location['description'] ?: 'Sin descripción registrada.' }}</div>

                                    <div class="location-card__stats">
                                        <div class="location-card__stat">
                                            <span>Guías activas</span>
                                            <strong>{{ number_format($location['active_guides_count'] ?? 0) }}</strong>
                                        </div>
                                        <div class="location-card__stat">
                                            <span>Tipo</span>
                                            <strong>{{ $location['is_storage'] ? 'Almacenamiento' : 'Normal' }}</strong>
                                        </div>
                                    </div>

                                    <div class="location-card__visual">
                                        <div class="location-card__visual-art" aria-hidden="true">
                                            <span class="location-card__box location-card__box--back">
                                                <i class="fas fa-box"></i>
                                            </span>
                                            <span class="location-card__box location-card__box--middle">
                                                <i class="fas fa-box"></i>
                                            </span>
                                            <span class="location-card__box location-card__box--front">
                                                <i class="fas fa-box-open"></i>
                                            </span>
                                        </div>
                                        <div class="location-card__visual-copy">
                                            <div class="location-card__visual-kicker">Cajas dentro</div>
                                            <div class="location-card__visual-count">{{ number_format($location['active_guides_count'] ?? 0) }}</div>
                                            <div class="location-card__visual-note">
                                                Cada guía representa una caja dentro de la ubicación.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="location-card__guides">
                                        <div class="location-card__guides-head">
                                            <span>Cajas / guías dentro</span>
                                            <span class="badge bg-light text-dark">{{ number_format($location['active_guides_count'] ?? 0) }}</span>
                                        </div>

                                        @if($previewGuides->isNotEmpty())
                                            @foreach($previewGuides as $guide)
                                                <article class="guide-tile">
                                                    <div class="guide-tile__header">
                                                        <div>
                                                            <div class="guide-tile__code">{{ $guide['guide'] }}</div>
                                                            <div class="guide-tile__meta">{{ $guide['entry_at'] ?: 'Sin fecha de ingreso' }}</div>
                                                        </div>
                                                        <span class="badge {{ $guide['status_badge_class'] }}">{{ $guide['status_label'] }}</span>
                                                    </div>

                                                    <div class="guide-tile__details">
                                                        <span class="badge bg-light text-dark">{{ $guide['duration_label'] }} en bodega</span>
                                                        <span class="guide-tile__source">Ingreso {{ strtoupper($guide['entry_source'] ?? 'manual') }}</span>
                                                    </div>

                                                    <div class="guide-tile__actions">
                                                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="openGuideTimeline('{{ $guide['guide'] }}')">
                                                            <i class="fas fa-eye me-1"></i>Ver
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openMoveGuideModal('{{ $guide['guide'] }}')">
                                                            <i class="fas fa-exchange-alt me-1"></i>Mover
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="openExitGuideModal('{{ $guide['guide'] }}')">
                                                            <i class="fas fa-sign-out-alt me-1"></i>Salida
                                                        </button>
                                                    </div>
                                                </article>
                                            @endforeach
                                        @else
                                            <div class="location-card__empty">
                                                <div class="location-card__empty-icon">
                                                    <i class="fas fa-box-open"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Sin guías en esta ubicación</div>
                                                    <div class="text-muted small">El espacio está disponible para recibir carga.</div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($remainingGuides > 0)
                                            <button type="button" class="location-card__more" onclick="openLocationDetail({{ $location['location_id'] }})">
                                                Ver {{ number_format($remainingGuides) }} caja{{ $remainingGuides === 1 ? '' : 's' }} más
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="location-card__footer">
                                    <div class="location-actions">
                                        <button type="button" class="btn btn-sm btn-outline-dark warehouse-action-btn" data-bs-toggle="tooltip" title="Ver ubicación" aria-label="Ver ubicación" onclick="openLocationDetail({{ $location['location_id'] }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary warehouse-action-btn" data-bs-toggle="tooltip" title="Editar ubicación" aria-label="Editar ubicación" onclick="openLocationModal({{ $location['location_id'] }})">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        @if(!$location['is_storage'])
                                            <button type="button" class="btn btn-sm btn-outline-danger warehouse-action-btn" data-bs-toggle="tooltip" title="Eliminar ubicación" aria-label="Eliminar ubicación" onclick="deleteLocation({{ $location['location_id'] }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @else
                                            <span data-bs-toggle="tooltip" title="Ubicación protegida">
                                                <button type="button" class="btn btn-sm btn-outline-secondary warehouse-action-btn" disabled aria-label="Ubicación protegida">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @else
        <div class="alert alert-light border">
            No hay ubicaciones registradas.
        </div>
    @endif
</div>
