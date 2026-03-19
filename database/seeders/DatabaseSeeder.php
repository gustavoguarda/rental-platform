<?php

namespace Database\Seeders;

use App\Models\Blockout;
use App\Models\Booking;
use App\Models\PricingRule;
use App\Models\Property;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate in correct order to avoid FK violations
        Blockout::query()->forceDelete();
        Booking::query()->forceDelete();
        PricingRule::query()->forceDelete();
        Property::query()->forceDelete();

        // ================================================================
        // PROPERTIES — 8 properties across 2 operators, varied statuses
        // ================================================================

        $properties = [
            // --- Operator 1: "Coastal Getaways" (5 properties) ---
            [
                'operator_id' => 1,
                'name' => 'Sunset Beach Villa',
                'slug' => 'sunset-beach-villa',
                'description' => 'Stunning oceanfront villa with panoramic views of the Atlantic. Floor-to-ceiling windows, private beach access, and a heated infinity pool overlooking the ocean.',
                'ai_description' => 'Experience paradise at this breathtaking 4-bedroom oceanfront villa in Miami Beach. Wake up to the sound of waves crashing against the shore, enjoy your morning coffee on the wraparound balcony with unobstructed Atlantic views, and spend your days lounging by the heated infinity pool. The villa features a gourmet kitchen with top-of-the-line appliances, a private hot tub, and direct beach access through a landscaped garden path. Perfect for families or groups seeking the ultimate beach vacation.',
                'address' => '123 Ocean Drive',
                'city' => 'Miami Beach',
                'state' => 'FL',
                'country' => 'US',
                'latitude' => 25.7617,
                'longitude' => -80.1918,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'max_guests' => 8,
                'base_price_cents' => 35000,
                'currency' => 'USD',
                'amenities' => ['pool', 'beach access', 'wifi', 'parking', 'hot tub', 'grill', 'washer/dryer', 'air conditioning', 'ocean view', 'smart TV'],
                'status' => 'active',
            ],
            [
                'operator_id' => 1,
                'name' => 'Mountain Lodge Retreat',
                'slug' => 'mountain-lodge-retreat',
                'description' => 'Cozy mountain cabin with rustic charm and modern amenities. Surrounded by the Great Smoky Mountains with breathtaking views from every room.',
                'ai_description' => 'Escape to this enchanting 3-bedroom mountain lodge nestled in the heart of the Great Smoky Mountains. The cabin combines rustic log construction with modern luxury — think heated floors, a gourmet kitchen, and a cedar-lined hot tub on the wraparound deck. Cozy up by the stone fireplace after a day of hiking, or stargaze from the covered porch. Minutes from downtown Gatlinburg attractions, yet worlds away from it all.',
                'address' => '456 Pine Ridge Road',
                'city' => 'Gatlinburg',
                'state' => 'TN',
                'country' => 'US',
                'latitude' => 35.7143,
                'longitude' => -83.5102,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'max_guests' => 6,
                'base_price_cents' => 22000,
                'currency' => 'USD',
                'amenities' => ['fireplace', 'hot tub', 'wifi', 'mountain view', 'hiking trails', 'game room', 'washer/dryer', 'fully equipped kitchen'],
                'status' => 'active',
            ],
            [
                'operator_id' => 1,
                'name' => 'Downtown Luxury Loft',
                'slug' => 'downtown-luxury-loft',
                'description' => 'Modern industrial loft in the heart of Nashville\'s music district. Exposed brick, 16-foot ceilings, and walking distance to Broadway.',
                'ai_description' => 'Live like a local in this stunning 2-bedroom industrial loft in Nashville\'s vibrant SoBro district. Original exposed brick walls meet contemporary design with 16-foot ceilings, polished concrete floors, and floor-to-ceiling windows flooding the space with natural light. Step outside and you\'re minutes from Broadway honky-tonks, world-class restaurants, and the Country Music Hall of Fame. The building features a rooftop terrace with skyline views and a fully equipped fitness center.',
                'address' => '789 Main Street, Unit 12A',
                'city' => 'Nashville',
                'state' => 'TN',
                'country' => 'US',
                'latitude' => 36.1627,
                'longitude' => -86.7816,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'max_guests' => 4,
                'base_price_cents' => 18000,
                'currency' => 'USD',
                'amenities' => ['wifi', 'gym', 'rooftop terrace', 'parking', 'smart TV', 'washer/dryer', 'air conditioning', 'city view', 'coffee maker', 'keyless entry'],
                'status' => 'active',
            ],
            [
                'operator_id' => 1,
                'name' => 'Desert Oasis',
                'slug' => 'desert-oasis',
                'description' => 'Mid-century modern architectural gem with private pool and mountain views. Recently renovated with designer furnishings throughout.',
                'address' => '200 Palm Canyon Drive',
                'city' => 'Palm Springs',
                'state' => 'CA',
                'country' => 'US',
                'latitude' => 33.8303,
                'longitude' => -116.5453,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'max_guests' => 6,
                'base_price_cents' => 28000,
                'currency' => 'USD',
                'amenities' => ['pool', 'wifi', 'parking', 'mountain view', 'outdoor shower', 'fire pit', 'air conditioning', 'smart TV'],
                'status' => 'active',
            ],
            [
                'operator_id' => 1,
                'name' => 'Seaside Cottage',
                'slug' => 'seaside-cottage',
                'description' => 'Charming Cape Cod style cottage steps from the beach. Recently listed, pending final review.',
                'address' => '55 Harbor Lane',
                'city' => 'Cape May',
                'state' => 'NJ',
                'country' => 'US',
                'latitude' => 38.9351,
                'longitude' => -74.9060,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'max_guests' => 4,
                'base_price_cents' => 15000,
                'currency' => 'USD',
                'amenities' => ['beach access', 'wifi', 'porch', 'grill', 'outdoor dining'],
                'status' => 'draft',
            ],

            // --- Operator 2: "Mountain & Lake Properties" (3 properties) ---
            [
                'operator_id' => 2,
                'name' => 'Lakeside Cabin',
                'slug' => 'lakeside-cabin',
                'description' => 'Spacious lakefront cabin with private dock and stunning sunset views. Perfect for large groups and family reunions.',
                'ai_description' => 'Welcome to your dream lakeside escape! This expansive 5-bedroom cabin sits directly on the shores of Lake Tahoe with a private dock, two kayaks, and a paddleboard included. The open-concept great room features vaulted ceilings with exposed timber beams and a massive stone fireplace. Wake up to mirror-like lake reflections, spend your days swimming and kayaking, and end with spectacular sunsets from the wraparound deck. Winter guests enjoy proximity to world-class skiing at Heavenly and Northstar resorts.',
                'address' => '100 Lakeshore Drive',
                'city' => 'Lake Tahoe',
                'state' => 'CA',
                'country' => 'US',
                'latitude' => 39.0968,
                'longitude' => -120.0324,
                'bedrooms' => 5,
                'bathrooms' => 3,
                'max_guests' => 10,
                'base_price_cents' => 45000,
                'currency' => 'USD',
                'amenities' => ['lake access', 'kayaks', 'paddleboard', 'wifi', 'fireplace', 'dock', 'grill', 'game room', 'washer/dryer', 'ski storage'],
                'status' => 'active',
            ],
            [
                'operator_id' => 2,
                'name' => 'Alpine Chalet',
                'slug' => 'alpine-chalet',
                'description' => 'Luxurious ski-in/ski-out chalet with hot tub and panoramic mountain views. Steps from the chairlift.',
                'ai_description' => 'The ultimate ski vacation starts here. This luxury 4-bedroom chalet offers true ski-in/ski-out access to Park City Mountain Resort, with a boot room, heated ski storage, and a bubbling hot tub on the deck overlooking pristine powder runs. After a day on the slopes, warm up in the chef\'s kitchen or sink into the leather sofas by the double-sided fireplace. The master suite features a soaking tub with mountain views.',
                'address' => '888 Powder Bowl Lane',
                'city' => 'Park City',
                'state' => 'UT',
                'country' => 'US',
                'latitude' => 40.6461,
                'longitude' => -111.4980,
                'bedrooms' => 4,
                'bathrooms' => 4,
                'max_guests' => 8,
                'base_price_cents' => 55000,
                'currency' => 'USD',
                'amenities' => ['ski-in/ski-out', 'hot tub', 'fireplace', 'wifi', 'mountain view', 'heated garage', 'boot room', 'smart TV', 'washer/dryer', 'wine cellar'],
                'status' => 'active',
            ],
            [
                'operator_id' => 2,
                'name' => 'Riverfront Tiny House',
                'slug' => 'riverfront-tiny-house',
                'description' => 'Minimalist tiny house on the river. Seasonal property — closed for winter maintenance.',
                'address' => '22 River Bend Road',
                'city' => 'Asheville',
                'state' => 'NC',
                'country' => 'US',
                'latitude' => 35.5951,
                'longitude' => -82.5515,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'max_guests' => 2,
                'base_price_cents' => 9500,
                'currency' => 'USD',
                'amenities' => ['river access', 'wifi', 'fire pit', 'hammock', 'outdoor shower'],
                'status' => 'inactive',
            ],
        ];

        foreach ($properties as $data) {
            Property::create($data);
        }

        // ================================================================
        // PRICING RULES — varied types showing dynamic pricing engine
        // ================================================================

        $villa = Property::where('slug', 'sunset-beach-villa')->first();
        $lodge = Property::where('slug', 'mountain-lodge-retreat')->first();
        $loft = Property::where('slug', 'downtown-luxury-loft')->first();
        $desert = Property::where('slug', 'desert-oasis')->first();
        $lake = Property::where('slug', 'lakeside-cabin')->first();
        $chalet = Property::where('slug', 'alpine-chalet')->first();

        // Villa pricing
        PricingRule::create([
            'property_id' => $villa->id,
            'name' => 'Summer Peak Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 25,
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'priority' => 10,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $villa->id,
            'name' => 'Spring Break Surge',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 35,
            'start_date' => '2026-03-14',
            'end_date' => '2026-03-28',
            'priority' => 15,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $villa->id,
            'name' => 'Weekend Premium',
            'type' => 'weekend',
            'modifier_type' => 'percentage',
            'modifier_value' => 15,
            'days_of_week' => [5, 6],
            'priority' => 5,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $villa->id,
            'name' => 'Off-Season Discount',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => -15,
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-15',
            'priority' => 8,
            'is_active' => true,
        ]);

        // Lodge pricing
        PricingRule::create([
            'property_id' => $lodge->id,
            'name' => 'Fall Foliage Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 20,
            'start_date' => '2026-10-01',
            'end_date' => '2026-11-15',
            'priority' => 10,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $lodge->id,
            'name' => 'Holiday Premium',
            'type' => 'seasonal',
            'modifier_type' => 'fixed',
            'modifier_value' => 5000,
            'start_date' => '2026-12-20',
            'end_date' => '2027-01-02',
            'priority' => 20,
            'is_active' => true,
        ]);

        // Loft pricing — event-based
        PricingRule::create([
            'property_id' => $loft->id,
            'name' => 'CMA Fest Week',
            'type' => 'seasonal',
            'modifier_type' => 'override',
            'modifier_value' => 45000,
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-08',
            'priority' => 25,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $loft->id,
            'name' => 'Weekend Rate',
            'type' => 'weekend',
            'modifier_type' => 'percentage',
            'modifier_value' => 20,
            'days_of_week' => [5, 6],
            'priority' => 5,
            'is_active' => true,
        ]);

        // Desert pricing
        PricingRule::create([
            'property_id' => $desert->id,
            'name' => 'Coachella / Stagecoach',
            'type' => 'seasonal',
            'modifier_type' => 'override',
            'modifier_value' => 85000,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-27',
            'priority' => 30,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $desert->id,
            'name' => 'Winter High Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 20,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-09',
            'priority' => 10,
            'is_active' => true,
        ]);

        // Lake pricing
        PricingRule::create([
            'property_id' => $lake->id,
            'name' => 'Summer Lake Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 30,
            'start_date' => '2026-06-15',
            'end_date' => '2026-09-01',
            'priority' => 10,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $lake->id,
            'name' => 'Ski Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 25,
            'start_date' => '2025-12-01',
            'end_date' => '2026-03-31',
            'priority' => 10,
            'is_active' => true,
        ]);

        // Chalet pricing
        PricingRule::create([
            'property_id' => $chalet->id,
            'name' => 'Peak Ski Season',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 40,
            'start_date' => '2025-12-15',
            'end_date' => '2026-03-31',
            'priority' => 15,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $chalet->id,
            'name' => 'Christmas / New Year',
            'type' => 'seasonal',
            'modifier_type' => 'override',
            'modifier_value' => 120000,
            'start_date' => '2026-12-20',
            'end_date' => '2027-01-03',
            'priority' => 30,
            'is_active' => true,
        ]);
        PricingRule::create([
            'property_id' => $chalet->id,
            'name' => 'Sundance Film Festival',
            'type' => 'seasonal',
            'modifier_type' => 'override',
            'modifier_value' => 95000,
            'start_date' => '2027-01-21',
            'end_date' => '2027-01-31',
            'priority' => 25,
            'is_active' => true,
        ]);

        // ================================================================
        // BOOKINGS — diverse statuses, channels, dates, and patterns
        // ================================================================

        // --- Villa bookings (high demand property) ---
        Booking::create([
            'property_id' => $villa->id,
            'guest_name' => 'James & Maria Rodriguez',
            'guest_email' => 'james.rodriguez@gmail.com',
            'check_in' => '2026-03-14',
            'check_out' => '2026-03-21',
            'guests_count' => 6,
            'total_price_cents' => 330750,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Anniversary trip. Requested champagne upon arrival.',
        ]);
        Booking::create([
            'property_id' => $villa->id,
            'guest_name' => 'The Chen Family',
            'guest_email' => 'lisa.chen@outlook.com',
            'check_in' => '2026-03-28',
            'check_out' => '2026-04-04',
            'guests_count' => 8,
            'total_price_cents' => 275000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'airbnb',
            'notes' => 'Family reunion. 2 children under 5. Need pack-n-play.',
        ]);
        Booking::create([
            'property_id' => $villa->id,
            'guest_name' => 'Robert Thompson',
            'guest_email' => 'r.thompson@yahoo.com',
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-13',
            'guests_count' => 2,
            'total_price_cents' => 105000,
            'currency' => 'USD',
            'status' => 'pending',
            'source_channel' => 'vrbo',
        ]);
        Booking::create([
            'property_id' => $villa->id,
            'guest_name' => 'Sarah Mitchell',
            'guest_email' => 'sarah.m@email.com',
            'check_in' => '2026-06-15',
            'check_out' => '2026-06-22',
            'guests_count' => 4,
            'total_price_cents' => 393750,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Returning guest. Third summer in a row!',
        ]);
        Booking::create([
            'property_id' => $villa->id,
            'guest_name' => 'David Kim',
            'guest_email' => 'david.kim@proton.me',
            'check_in' => '2026-02-10',
            'check_out' => '2026-02-14',
            'guests_count' => 2,
            'total_price_cents' => 140000,
            'currency' => 'USD',
            'status' => 'cancelled',
            'source_channel' => 'booking_com',
            'notes' => 'Cancelled due to flight change.',
        ]);

        // --- Lodge bookings ---
        Booking::create([
            'property_id' => $lodge->id,
            'guest_name' => 'The Patel Family',
            'guest_email' => 'anita.patel@gmail.com',
            'check_in' => '2026-03-20',
            'check_out' => '2026-03-25',
            'guests_count' => 5,
            'total_price_cents' => 110000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'airbnb',
        ]);
        Booking::create([
            'property_id' => $lodge->id,
            'guest_name' => 'Mark & Jennifer Wilson',
            'guest_email' => 'mark.wilson@outlook.com',
            'check_in' => '2026-04-05',
            'check_out' => '2026-04-12',
            'guests_count' => 4,
            'total_price_cents' => 138600,
            'currency' => 'USD',
            'status' => 'pending',
            'source_channel' => 'vrbo',
            'notes' => 'Would like early check-in if possible.',
        ]);
        Booking::create([
            'property_id' => $lodge->id,
            'guest_name' => 'Tom Baker',
            'guest_email' => 'tom.baker@email.com',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests_count' => 2,
            'total_price_cents' => 132000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Fall foliage photography trip.',
        ]);

        // --- Loft bookings ---
        Booking::create([
            'property_id' => $loft->id,
            'guest_name' => 'Amanda Foster',
            'guest_email' => 'amanda.foster@gmail.com',
            'check_in' => '2026-03-19',
            'check_out' => '2026-03-22',
            'guests_count' => 2,
            'total_price_cents' => 54000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Bachelorette weekend. Need restaurant recommendations.',
        ]);
        Booking::create([
            'property_id' => $loft->id,
            'guest_name' => 'Carlos Hernandez',
            'guest_email' => 'carlos.h@email.com',
            'check_in' => '2026-06-04',
            'check_out' => '2026-06-08',
            'guests_count' => 4,
            'total_price_cents' => 180000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'airbnb',
            'notes' => 'CMA Fest trip!',
        ]);
        Booking::create([
            'property_id' => $loft->id,
            'guest_name' => 'Jessica Lee',
            'guest_email' => 'jessica.lee@yahoo.com',
            'check_in' => '2026-04-17',
            'check_out' => '2026-04-19',
            'guests_count' => 1,
            'total_price_cents' => 43200,
            'currency' => 'USD',
            'status' => 'pending',
            'source_channel' => 'booking_com',
        ]);

        // --- Desert bookings ---
        Booking::create([
            'property_id' => $desert->id,
            'guest_name' => 'The Williams Group',
            'guest_email' => 'kate.williams@gmail.com',
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-13',
            'guests_count' => 6,
            'total_price_cents' => 255000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Coachella Weekend 1. Will arrive late Friday.',
        ]);
        Booking::create([
            'property_id' => $desert->id,
            'guest_name' => 'Ryan & Sophia Clark',
            'guest_email' => 'ryan.clark@proton.me',
            'check_in' => '2026-02-14',
            'check_out' => '2026-02-18',
            'guests_count' => 2,
            'total_price_cents' => 134400,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'vrbo',
            'notes' => 'Valentine\'s Day getaway.',
        ]);

        // --- Lake bookings ---
        Booking::create([
            'property_id' => $lake->id,
            'guest_name' => 'The Martinez Family Reunion',
            'guest_email' => 'elena.martinez@outlook.com',
            'check_in' => '2026-07-04',
            'check_out' => '2026-07-11',
            'guests_count' => 10,
            'total_price_cents' => 546000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => '4th of July week. All 10 guests. Need extra towels.',
        ]);
        Booking::create([
            'property_id' => $lake->id,
            'guest_name' => 'Alex & Jordan Taylor',
            'guest_email' => 'alex.taylor@gmail.com',
            'check_in' => '2026-03-21',
            'check_out' => '2026-03-28',
            'guests_count' => 4,
            'total_price_cents' => 393750,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'airbnb',
            'notes' => 'Ski trip. Arriving from San Francisco.',
        ]);
        Booking::create([
            'property_id' => $lake->id,
            'guest_name' => 'Peter & Susan Brown',
            'guest_email' => 'peter.brown@email.com',
            'check_in' => '2026-08-15',
            'check_out' => '2026-08-22',
            'guests_count' => 6,
            'total_price_cents' => 409500,
            'currency' => 'USD',
            'status' => 'pending',
            'source_channel' => 'vrbo',
        ]);

        // --- Chalet bookings ---
        Booking::create([
            'property_id' => $chalet->id,
            'guest_name' => 'Michael & Anna Novak',
            'guest_email' => 'michael.novak@gmail.com',
            'check_in' => '2026-03-20',
            'check_out' => '2026-03-27',
            'guests_count' => 6,
            'total_price_cents' => 539000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
            'notes' => 'Expert skiers. Interested in heli-skiing day trips.',
        ]);
        Booking::create([
            'property_id' => $chalet->id,
            'guest_name' => 'The Johnson Ski Group',
            'guest_email' => 'dave.johnson@yahoo.com',
            'check_in' => '2026-01-15',
            'check_out' => '2026-01-19',
            'guests_count' => 8,
            'total_price_cents' => 308000,
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'airbnb',
        ]);
        Booking::create([
            'property_id' => $chalet->id,
            'guest_name' => 'Emily Wright',
            'guest_email' => 'emily.wright@email.com',
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-15',
            'guests_count' => 2,
            'total_price_cents' => 275000,
            'currency' => 'USD',
            'status' => 'cancelled',
            'source_channel' => 'booking_com',
            'notes' => 'Cancelled: season ended early, not enough snow.',
        ]);

        // ================================================================
        // BLOCKOUTS — maintenance, owner use, renovations
        // ================================================================

        Blockout::create([
            'property_id' => $villa->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-10',
            'reason' => 'Annual deep cleaning and pool maintenance',
        ]);
        Blockout::create([
            'property_id' => $villa->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-07',
            'reason' => 'Owner personal use — Labor Day week',
        ]);
        Blockout::create([
            'property_id' => $lodge->id,
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-25',
            'reason' => 'Deck repairs and hot tub replacement',
        ]);
        Blockout::create([
            'property_id' => $loft->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'reason' => 'Building-wide fire alarm inspection',
        ]);
        Blockout::create([
            'property_id' => $lake->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-15',
            'reason' => 'Dock repair and boat launch renovation',
        ]);
        Blockout::create([
            'property_id' => $chalet->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-15',
            'reason' => 'Off-season renovation — kitchen and bathroom remodel',
        ]);
        Blockout::create([
            'property_id' => $desert->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'reason' => 'Too hot for rentals — seasonal closure',
        ]);
    }
}
