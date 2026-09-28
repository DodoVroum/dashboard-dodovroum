<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DodoVroumApiService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Support\BookingFinance;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DodoVroumApiService $apiService
    ) {
    }

    private function extractData(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data']) && isset($data['data'][0])) {
                return $data['data'];
            } elseif (is_array($data['data'])) {
                return [$data['data']];
            }
        }
        return $data;
    }

    public function __invoke(): Response
    {
        try {
            // Récupérer toutes les données (pas de filtre proprietaireId pour admin)
            $allResidences = $this->apiService->getResidences([]);
            $allVehicles = $this->apiService->getVehicles([]);
            $allBookings = $this->apiService->getBookings([]);
            $allComboOffers = $this->apiService->getComboOffers([]);
            
            // Calculer les statistiques détaillées
            $stats = $this->calculateDetailedStats($allResidences, $allVehicles, $allBookings, $allComboOffers);
            
            // Récupérer les réservations récentes (5 dernières)
            $recentBookings = array_slice($allBookings, 0, 5);
            
            // Générer les alertes
            $alerts = $this->generateAlerts($allResidences, $allVehicles, $allBookings);

            // Fetch all users once to map client IDs to names
            $usersMap = [];
            try {
                $usersData = $this->apiService->getUsers();
                $allUsers = $this->extractData($usersData);
                foreach ($allUsers as $user) {
                    $userId = $user['id'] ?? $user['_id'] ?? null;
                    if ($userId) {
                        $firstName = $user['firstName'] ?? $user['prenom'] ?? '';
                        $lastName = $user['lastName'] ?? $user['nom'] ?? $user['name'] ?? '';
                        $fullName = trim($firstName . ' ' . $lastName);
                        if (empty($fullName)) {
                            $fullName = $user['email'] ?? 'Client inconnu';
                        }
                        $usersMap[$userId] = $fullName;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch users for dashboard booking mapping', ['error' => $e->getMessage()]);
            }

            // Map bookings for the frontend
            $mappedBookings = array_map(function ($booking) use ($usersMap, $allResidences, $allVehicles) {
                // Extract customer name
                $customerName = 'Client inconnu';
                if (isset($booking['user'])) {
                    $user = $booking['user'];
                    $firstName = $user['firstName'] ?? $user['prenom'] ?? '';
                    $lastName = $user['lastName'] ?? $user['nom'] ?? $user['name'] ?? '';
                    $customerName = trim($firstName . ' ' . $lastName);
                    if (empty($customerName)) {
                        $customerName = $user['email'] ?? 'Client inconnu';
                    }
                } elseif (isset($booking['customer_name'])) {
                    $customerName = $booking['customer_name'];
                } elseif (isset($booking['customer'])) {
                    $customerName = $booking['customer'];
                } elseif (isset($booking['clientId']) && isset($usersMap[$booking['clientId']])) {
                    $customerName = $usersMap[$booking['clientId']];
                } elseif (isset($booking['clientId'])) {
                    $customerName = 'Client #' . substr($booking['clientId'], 0, 8);
                }

                // Extract property/vehicle/offer name
                $propertyName = null;
                
                // Vérifier d'abord si c'est une résidence
                if (isset($booking['residenceName']) && !empty($booking['residenceName'])) {
                    $propertyName = $booking['residenceName'];
                } elseif (isset($booking['residence']) && is_array($booking['residence'])) {
                    $residence = $booking['residence'];
                    $propertyName = $residence['nom'] ?? $residence['name'] ?? $residence['title'] ?? null;
                } elseif (isset($booking['property_name']) && !empty($booking['property_name'])) {
                    $propertyName = $booking['property_name'];
                } elseif (isset($booking['residenceId']) && !empty($booking['residenceId'])) {
                    // Si on a un residenceId mais pas les détails, chercher dans les résidences récupérées
                    $residenceId = $booking['residenceId'];
                    foreach ($allResidences as $residence) {
                        $resId = $residence['id'] ?? $residence['_id'] ?? null;
                        if ($resId && ((string) $resId === (string) $residenceId || (is_numeric($resId) && is_numeric($residenceId) && (int) $resId === (int) $residenceId))) {
                            $propertyName = $residence['nom'] ?? $residence['name'] ?? $residence['title'] ?? null;
                            break;
                        }
                    }
                }
                
                // Si pas de résidence, vérifier si c'est un véhicule
                if (!$propertyName) {
                    if (isset($booking['vehicle']) && is_array($booking['vehicle'])) {
                        $vehicle = $booking['vehicle'];
                        $propertyName = $vehicle['titre'] ?? $vehicle['name'] ?? null;
                        if (!$propertyName) {
                            $marque = $vehicle['marque'] ?? $vehicle['brand'] ?? '';
                            $modele = $vehicle['modele'] ?? $vehicle['model'] ?? '';
                            $propertyName = trim($marque . ' ' . $modele);
                        }
                    } elseif (isset($booking['voiture']) && is_array($booking['voiture'])) {
                        $vehicle = $booking['voiture'];
                        $propertyName = $vehicle['titre'] ?? $vehicle['name'] ?? null;
                        if (!$propertyName) {
                            $marque = $vehicle['marque'] ?? $vehicle['brand'] ?? '';
                            $modele = $vehicle['modele'] ?? $vehicle['model'] ?? '';
                            $propertyName = trim($marque . ' ' . $modele);
                        }
                    } elseif (isset($booking['vehicleName']) && !empty($booking['vehicleName'])) {
                        $propertyName = $booking['vehicleName'];
                    } elseif (isset($booking['vehicleId']) && !empty($booking['vehicleId'])) {
                        // Si on a un vehicleId mais pas les détails, chercher dans les véhicules récupérés
                        $vehicleId = $booking['vehicleId'];
                        foreach ($allVehicles as $vehicle) {
                            $vehId = $vehicle['id'] ?? $vehicle['_id'] ?? null;
                            if ($vehId && ((string) $vehId === (string) $vehicleId || (is_numeric($vehId) && is_numeric($vehicleId) && (int) $vehId === (int) $vehicleId))) {
                                $propertyName = $vehicle['titre'] ?? $vehicle['name'] ?? null;
                                if (!$propertyName) {
                                    $marque = $vehicle['marque'] ?? $vehicle['brand'] ?? '';
                                    $modele = $vehicle['modele'] ?? $vehicle['model'] ?? '';
                                    $propertyName = trim($marque . ' ' . $modele);
                                }
                                break;
                            }
                        }
                    }
                }
                
                // Si pas de résidence ni véhicule, vérifier si c'est une offre combinée
                if (!$propertyName && isset($booking['offer']) && is_array($booking['offer'])) {
                    $offer = $booking['offer'];
                    $propertyName = $offer['titre'] ?? $offer['title'] ?? $offer['name'] ?? null;
                } elseif (!$propertyName && isset($booking['offerName']) && !empty($booking['offerName'])) {
                    $propertyName = $booking['offerName'];
                }
                
                // Fallback si rien n'est trouvé
                if (!$propertyName) {
                    $propertyName = 'Non spécifié';
                }

                // Extract dates
                $startDate = $booking['startDate'] ?? $booking['start_date'] ?? $booking['checkInDate'] ?? null;
                $endDate = $booking['endDate'] ?? $booking['end_date'] ?? $booking['checkOutDate'] ?? null;
                
                $dates = '';
                if ($startDate && $endDate) {
                    try {
                        $start = new \DateTime($startDate);
                        $end = new \DateTime($endDate);
                        $dates = $start->format('d M') . ' - ' . $end->format('d M');
                    } catch (\Exception $e) {
                        $dates = $startDate . ' - ' . $endDate;
                    }
                }

                // Format status (même logique que AdminBookingController)
                $rawStatus = $booking['status'] ?? 'pending';
                $statusUpper = strtoupper($rawStatus);
                $ownerConfirmedAt = $booking['ownerConfirmedAt'] ?? $booking['owner_confirmed_at'] ?? null;
                $checkOutAt = $booking['checkOutAt'] ?? $booking['check_out_at'] ?? null;
                $createdAt = $booking['createdAt'] ?? $booking['created_at'] ?? null;

                $isStayCompleted = false;
                if ($endDate) {
                    try {
                        $endDateTime = new \DateTime($endDate);
                        $now = new \DateTime();
                        $isStayCompleted = $endDateTime < $now;
                    } catch (\Exception $e) {
                    }
                }

                if (!empty($checkOutAt)) {
                    $finalStatus = 'terminée';
                } elseif ($isStayCompleted) {
                    $finalStatus = 'terminée';
                } elseif ($statusUpper === 'AWAITING_PAYMENT') {
                    $finalStatus = 'awaiting_payment';
                } elseif (!empty($ownerConfirmedAt)) {
                    $finalStatus = 'confirmed';
                } elseif ($statusUpper === 'PENDING') {
                    $finalStatus = 'pending';
                } elseif (($statusUpper === 'CONFIRMEE' || $statusUpper === 'CONFIRMED') && empty($ownerConfirmedAt)) {
                    $finalStatus = 'pending';
                } else {
                    $finalStatus = strtolower($rawStatus);
                }

                // Harmoniser avec la page Réservations:
                // une réservation en attente > 5 minutes devient "expired".
                if ($finalStatus === 'pending' && !empty($createdAt)) {
                    try {
                        $createdAtTime = new \DateTimeImmutable((string) $createdAt);
                        $now = new \DateTimeImmutable();
                        $pendingAgeSeconds = $now->getTimestamp() - $createdAtTime->getTimestamp();
                        if ($pendingAgeSeconds > 5 * 60) {
                            $finalStatus = 'expired';
                        }
                    } catch (\Exception $e) {
                        // Ignorer les dates invalides et garder le statut calculé.
                    }
                }
                
                $statusFormatted = 'En attente';
                if (strtolower($finalStatus) === 'confirmed' || strtolower($finalStatus) === 'confirmee') {
                    $statusFormatted = 'Confirmée';
                } elseif (strtolower($finalStatus) === 'awaiting_payment') {
                    $statusFormatted = 'En attente de paiement';
                } elseif (strtolower($finalStatus) === 'pending') {
                    $statusFormatted = 'En attente';
                } elseif (strtolower($finalStatus) === 'expired') {
                    $statusFormatted = 'Expirée';
                } elseif (strtolower($finalStatus) === 'cancelled' || strtolower($finalStatus) === 'canceled' || strtolower($finalStatus) === 'annulée' || strtolower($finalStatus) === 'annulee') {
                    $statusFormatted = 'Annulée';
                } elseif (strtolower($finalStatus) === 'completed' || strtolower($finalStatus) === 'terminee' || strtolower($finalStatus) === 'terminée') {
                    $statusFormatted = 'Terminée';
                }

                return [
                    'id' => $booking['id'] ?? $booking['_id'] ?? null,
                    'customer' => $customerName,
                    'property' => $propertyName,
                    'dates' => $dates,
                    'status' => $statusFormatted,
                ];
            }, $recentBookings);

        } catch (\Exception $e) {
            // En cas d'erreur API, utiliser des valeurs par défaut
            Log::error('Erreur récupération données dashboard admin', ['error' => $e->getMessage()]);
            
            $stats = [
                'residences' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'withoutImage' => 0, 'last' => null],
                'vehicles' => ['total' => 0, 'available' => 0, 'unavailable' => 0, 'withoutImage' => 0, 'withoutPrice' => 0, 'last' => null],
                'bookings' => ['total' => 0, 'active' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0],
                'revenue' => ['today' => 0, 'month' => 0, 'total' => 0, 'volumeToday' => 0, 'volumeMonth' => 0, 'volumeTotal' => 0, 'paidBookings' => 0, 'unpaidBookings' => 0],
                'comboOffers' => 0,
            ];
            $mappedBookings = [];
            $alerts = [];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentBookings' => $mappedBookings,
            'alerts' => $alerts ?? [],
        ]);
    }
    
    /**
     * Calculer les statistiques détaillées
     */
    private function calculateDetailedStats(array $residences, array $vehicles, array $bookings, array $comboOffers): array
    {
        // Stats résidences
        $activeResidences = 0;
        $inactiveResidences = 0;
        $residencesWithoutImage = 0;
        $lastResidence = null;
        
        foreach ($residences as $residence) {
            $isActive = $residence['isActive'] ?? $residence['is_active'] ?? $residence['available'] ?? true;
            if ($isActive === true || $isActive === 'true' || $isActive === 1) {
                $activeResidences++;
            } else {
                $inactiveResidences++;
            }
            
            // Vérifier si sans image
            if (empty($residence['imageUrl'] ?? $residence['image_url'] ?? $residence['images'] ?? [])) {
                $residencesWithoutImage++;
            }
            
            // Dernière résidence (par ID ou date de création)
            if (!$lastResidence) {
                $lastResidence = $residence;
            }
        }
        
        // Stats véhicules
        $availableVehicles = 0;
        $unavailableVehicles = 0;
        $vehiclesWithoutImage = 0;
        $vehiclesWithoutPrice = 0;
        $lastVehicle = null;
        
        foreach ($vehicles as $vehicle) {
            $isAvailable = $vehicle['available'] ?? $vehicle['isAvailable'] ?? $vehicle['is_available'] ?? true;
            if ($isAvailable === true || $isAvailable === 'true' || $isAvailable === 1) {
                $availableVehicles++;
            } else {
                $unavailableVehicles++;
            }
            
            // Vérifier si sans image
            if (empty($vehicle['imageUrl'] ?? $vehicle['image_url'] ?? $vehicle['images'] ?? [])) {
                $vehiclesWithoutImage++;
            }
            
            // Vérifier si sans prix
            $price = $vehicle['prixParJour'] ?? $vehicle['pricePerDay'] ?? $vehicle['price'] ?? 0;
            if (empty($price) || $price == 0) {
                $vehiclesWithoutPrice++;
            }
            
            // Dernier véhicule
            if (!$lastVehicle) {
                $lastVehicle = $vehicle;
            }
        }
        
        // Stats réservations
        $activeBookings = 0;
        $pendingBookings = 0;
        $completedBookings = 0;
        $cancelledBookings = 0;
        
        foreach ($bookings as $booking) {
            // Déterminer le statut réel en vérifiant ownerConfirmedAt
            $rawStatus = $booking['status'] ?? 'pending';
            $statusUpper = strtoupper($rawStatus);
            $ownerConfirmedAt = $booking['ownerConfirmedAt'] ?? $booking['owner_confirmed_at'] ?? null;
            
            // Si le statut est "confirmed" mais qu'il n'y a pas de ownerConfirmedAt,
            // c'est une réservation en PENDING qui n'a pas encore été approuvée
            if (($statusUpper === 'CONFIRMEE' || $statusUpper === 'CONFIRMED') && empty($ownerConfirmedAt)) {
                $finalStatus = 'pending';
            } else {
                $finalStatus = strtolower($rawStatus);
            }
            
            if ($finalStatus === 'confirmed' || $finalStatus === 'confirmee' || $finalStatus === 'active') {
                $activeBookings++;
            } elseif ($finalStatus === 'pending' || $finalStatus === 'en attente') {
                $pendingBookings++;
            } elseif ($finalStatus === 'completed' || $finalStatus === 'terminee' || $finalStatus === 'terminée') {
                $completedBookings++;
            } elseif ($finalStatus === 'cancelled' || $finalStatus === 'annulee' || $finalStatus === 'annulée') {
                $cancelledBookings++;
            }
        }
        
        // Revenus DodoVroum, montants calculés par l'API : comptabilisés dès la
        // confirmation du propriétaire. Volume = 100 %, commission = 10 % du totalPrice.
        $validBookings = array_filter($bookings, fn ($booking) => is_array($booking) && BookingFinance::isPlatformRealized($booking));
        $realizedToday = BookingFinance::platformRealized($bookings, BookingFinance::dayStart());
        $realizedMonth = BookingFinance::platformRealized($bookings, BookingFinance::monthStart());
        $realizedTotal = BookingFinance::platformRealized($bookings);
        $revenueToday = $realizedToday['commission'];
        $revenueMonth = $realizedMonth['commission'];
        $revenueTotal = $realizedTotal['commission'];
        $paidBookings = 0;
        $unpaidBookings = 0;

        foreach ($validBookings as $booking) {
            // Vérifier si payé (simplifié - à améliorer avec les données de paiement)
            $isPaid = false;
            if (isset($booking['payments']) && is_array($booking['payments'])) {
                foreach ($booking['payments'] as $payment) {
                    $paymentStatus = strtolower($payment['status'] ?? '');
                    if (in_array($paymentStatus, ['completed', 'paid', 'validated'])) {
                        $isPaid = true;
                        break;
                    }
                }
            }

            if ($isPaid) {
                $paidBookings++;
            } else {
                $unpaidBookings++;
            }
        }

        $stats = [
            'residences' => [
                'total' => count($residences),
                'active' => $activeResidences,
                'inactive' => $inactiveResidences,
                'withoutImage' => $residencesWithoutImage,
                'last' => $lastResidence ? [
                    'id' => $lastResidence['id'] ?? $lastResidence['_id'] ?? null,
                    'name' => $lastResidence['nom'] ?? $lastResidence['name'] ?? $lastResidence['title'] ?? 'Résidence sans nom',
                ] : null,
            ],
            'vehicles' => [
                'total' => count($vehicles),
                'available' => $availableVehicles,
                'unavailable' => $unavailableVehicles,
                'withoutImage' => $vehiclesWithoutImage,
                'withoutPrice' => $vehiclesWithoutPrice,
                'last' => $lastVehicle ? [
                    'id' => $lastVehicle['id'] ?? $lastVehicle['_id'] ?? null,
                    'name' => $lastVehicle['titre'] ?? $lastVehicle['name'] ?? ($lastVehicle['marque'] ?? '') . ' ' . ($lastVehicle['modele'] ?? ''),
                ] : null,
            ],
            'bookings' => [
                'total' => count($bookings),
                'active' => $activeBookings,
                'pending' => $pendingBookings,
                'completed' => $completedBookings,
                'cancelled' => $cancelledBookings,
            ],
            'revenue' => [
                'today' => $revenueToday,
                'month' => $revenueMonth,
                'total' => $revenueTotal,
                // Volume des réservations réalisées (100 % du totalPrice)
                'volumeToday' => $realizedToday['bookingValue'],
                'volumeMonth' => $realizedMonth['bookingValue'],
                'volumeTotal' => $realizedTotal['bookingValue'],
                'paidBookings' => $paidBookings,
                'unpaidBookings' => $unpaidBookings,
            ],
            'comboOffers' => count($comboOffers),
        ];
        
        Log::info('AdminDashboardController - Statistiques revenus calculées', [
            'revenueTotal' => $revenueTotal,
            'revenueMonth' => $revenueMonth,
            'revenueToday' => $revenueToday,
            'validBookingsCount' => count($validBookings),
        ]);
        
        return $stats;
    }

    /**
     * Générer les alertes pour l'admin
     */
    private function generateAlerts(array $residences, array $vehicles, array $bookings): array
    {
        $alerts = [];
        
        // Réservations en attente
        $pendingBookings = array_filter($bookings, function($booking) {
            // Déterminer le statut réel en vérifiant ownerConfirmedAt
            $rawStatus = $booking['status'] ?? 'pending';
            $statusUpper = strtoupper($rawStatus);
            $ownerConfirmedAt = $booking['ownerConfirmedAt'] ?? $booking['owner_confirmed_at'] ?? null;
            
            // Si le statut est "confirmed" mais qu'il n'y a pas de ownerConfirmedAt,
            // c'est une réservation en PENDING qui n'a pas encore été approuvée
            if (($statusUpper === 'CONFIRMEE' || $statusUpper === 'CONFIRMED') && empty($ownerConfirmedAt)) {
                return true; // Considérer comme pending
            }
            
            $status = strtolower($rawStatus);
            return $status === 'pending' || $status === 'en attente';
        });
        if (count($pendingBookings) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($pendingBookings) . ' réservation(s) en attente de validation',
                'action' => 'Voir les réservations',
                'href' => '/admin/bookings?status=pending',
            ];
        }
        
        // Résidences sans photo
        $residencesWithoutImage = array_filter($residences, function($residence) {
            return empty($residence['imageUrl'] ?? $residence['image_url'] ?? $residence['images'] ?? []);
        });
        if (count($residencesWithoutImage) > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => count($residencesWithoutImage) . ' résidence(s) sans photo',
                'action' => 'Voir les résidences',
                'href' => '/admin/residences',
            ];
        }
        
        // Véhicules sans photo
        $vehiclesWithoutImage = array_filter($vehicles, function($vehicle) {
            return empty($vehicle['imageUrl'] ?? $vehicle['image_url'] ?? $vehicle['images'] ?? []);
        });
        if (count($vehiclesWithoutImage) > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => count($vehiclesWithoutImage) . ' véhicule(s) sans photo',
                'action' => 'Voir les véhicules',
                'href' => '/admin/vehicles',
            ];
        }
        
        // Véhicules sans prix
        $vehiclesWithoutPrice = array_filter($vehicles, function($vehicle) {
            $price = $vehicle['prixParJour'] ?? $vehicle['pricePerDay'] ?? $vehicle['price'] ?? 0;
            return empty($price) || $price == 0;
        });
        if (count($vehiclesWithoutPrice) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($vehiclesWithoutPrice) . ' véhicule(s) sans prix',
                'action' => 'Voir les véhicules',
                'href' => '/admin/vehicles',
            ];
        }
        
        return $alerts;
    }
}
