<?php

return [
    // P9: các ngưỡng điều phối gom về config để dễ tinh chỉnh khi chạy thực tế.
    'shortlist' => [
        'available_candidates' => 6,
        'returning_candidates' => 4,
        'bundle_candidates' => 6,
        'available_air_km' => 10.0,
        'returning_air_km' => 10.0,
    ],

    'available' => [
        'max_pickup_eta_minutes' => 22.0,
        'same_branch_bonus' => 18.0,
        'floating_bonus' => 4.0,
        'eta_penalty_per_minute' => 2.4,
        'idle_bonus_per_10_minutes' => 0.8,
        'max_idle_bonus' => 7.0,
        'branch_reserve_penalty' => 5.0,
        'branch_demand_penalty' => 1.8,
    ],

    'returning' => [
        'max_pickup_eta_minutes' => 18.0,
        'same_target_branch_bonus' => 16.0,
        'eta_penalty_per_minute' => 2.2,
        'detour_penalty_per_minute' => 1.2,
        'branch_reserve_penalty' => 4.0,
        'branch_demand_penalty' => 1.5,
    ],

    'bundle' => [
        'max_orders_per_trip' => 3,
        'max_cups_per_trip' => 20,
        'far_order_km' => 8.0,
        'max_trip_minutes' => 75.0,
        'normal_min_saved_m' => 500.0,
        'far_pair_max_distance_ratio' => 1.02,
        'max_existing_customer_delay_minutes' => 12.0,
        'max_scheduled_lateness_minutes' => 15.0,
        'saved_km_weight' => 4.0,
        'saved_ratio_weight' => 30.0,
        'far_pair_bonus' => 20.0,
        'existing_delay_penalty_per_minute' => 2.0,
        'high_load_penalty' => 4.0,
    ],

    'waiting_orders' => [
        'scan_limit' => 20,
        'dispatch_limit_per_trigger' => 5,
    ],
];
