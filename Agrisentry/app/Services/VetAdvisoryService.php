<?php

namespace App\Services;

class VetAdvisoryService
{
    public static function temperatureRecommendation(?float $temperature): ?string
    {
        if ($temperature === null || !is_finite($temperature)) return null;

        $reading = number_format($temperature, 1) . '°C';
        [$label, $steps] = match (true) {
            $temperature < 32.0 => ['Urgent Low', 'Inspect the goat immediately and contact a veterinarian promptly. Move it out of cold or wet conditions into dry shelter. Check the collar contact and repeat the reading while watching for weakness or difficulty standing.'],
            $temperature < 33.0 => ['Warning Low', 'Check for wet bedding, drafts, and poor collar contact. Keep the goat in dry shelter and recheck the reading promptly. Watch its appetite and activity; seek veterinary help if the reading falls or the goat appears unwell.'],
            $temperature < 35.0 => ['Normal', 'This is in the lower part of the configured monitoring range. Check that the collar is seated correctly and the resting area is dry. Compare the next reading for a downward trend and observe appetite and movement.'],
            $temperature < 37.0 => ['Normal', 'This is in the middle of the configured monitoring range. Continue regular checks of feeding, drinking, and movement. Keep bedding clean and dry, and compare later readings with this goat\'s usual pattern.'],
            $temperature <= 38.5 => ['Normal', 'This is in the upper part of the configured monitoring range. Check access to shade, drinking water, and ventilation. Watch the next reading for a rise above 38.5°C and check for unusual breathing or reduced activity.'],
            $temperature <= 39.5 => ['Warning High', 'Move the goat into shade and provide clean drinking water and ventilation. Reduce exertion and check the collar reading again promptly. Watch for unusual breathing or reduced activity; seek veterinary help if the reading rises or symptoms appear.'],
            default => ['Urgent High', 'Inspect the goat immediately and contact a veterinarian promptly. Move it to a shaded, ventilated area and make drinking water available. Verify the collar contact and monitor breathing and ability to stand while arranging help.'],
        };

        // Vary routine observation prompts at the displayed 0.1°C precision.
        // These are checklist variations, not additional clinical thresholds.
        $tenths = (int) round(abs($temperature) * 10);
        $focus = [
            'Observe the goat during feeding and note whether it eats as usual.',
            'Check the resting area for wet bedding and replace any damp material.',
            'Inspect the collar position and sensor contact before comparing the next reading.',
            'Watch the goat walking and note any change in its usual movement.',
            'Check that drinking water is clean and easy for the goat to reach.',
            'Observe the goat at rest and note any unusual breathing or behavior.',
            'Watch appetite and chewing activity, and record any change from its usual routine.',
            'Check whether the goat is staying with the herd or spending unusual time alone.',
            'Inspect the shelter for drafts, dampness, or direct sun on the resting area.',
            'Review the recent readings and note whether this value is rising, falling, or steady.',
        ][$tenths % 10];
        $followUp = [
            'Record the conditions around the sensor so the next check has useful context.',
            'Compare the follow-up reading with this goat\'s recent pattern and note any continued change.',
            'At the next routine check, record another reading together with the goat\'s activity.',
            'Make a short caretaker note about feeding, water access, and behavior for the next shift.',
            'Keep the usual monitoring schedule and record any new change in behavior.',
            'Compare readings taken under similar resting conditions to make changes easier to assess.',
            'Check the next recorded value and flag a continuing rise or fall for the caretaker.',
            'Share the reading and your observations with the person doing the next check.',
            'Record when the goat last rested or moved into a different environment.',
            'Keep a note of the reading and any visible changes for follow-up assessment.',
        ][intdiv($tenths, 10) % 10];

        if ($label === 'Normal') {
            $position = $temperature < 35.0 ? 'lower part' : ($temperature < 37.0 ? 'middle' : 'upper part');
            $steps = "{$focus} {$followUp} This reading is in the {$position} of the configured monitoring range.";
        } else {
            // Always retain the severity-specific actions, especially urgent escalation.
            $steps = "{$steps} {$focus} {$followUp}";
        }

        return "{$label} — Skin reading {$reading}: {$steps} This is a skin-sensor reading, not a core body temperature or diagnosis.";
    }
    /**
     * Canned recommendations shown on-screen and sent via SMS whenever an
     * Alert is created. Kept separate from the Gemini AI chat.
     */
    private const ADVICE = [
        'High Temperature Warning' => 'Move the goat to shade, offer water, reduce exertion, and repeat the reading within 15 minutes. Escalate if it rises or symptoms appear.',
        'Low Temperature Warning' => 'Move the goat to a dry shelter, check for wetness or weakness, and repeat the reading within 15 minutes. Escalate if it falls or symptoms appear.',
        'Prolonged Inactivity' => 'Check immediately for weakness, entrapment, injury, or illness. Contact a veterinarian if the goat cannot stand or remains inactive.',
        'Excessive Movement' => 'Inspect immediately for panic, distress, entanglement, or seizure-like activity. Remove hazards and contact a veterinarian if it continues.',
        'High Temperature' => 'Give the goat enough water to prevent dehydration and move it to a shaded, ventilated area. Arrange prompt veterinary assessment.',
        'Low Temperature' => 'Move the goat to a warm, dry, sheltered area with dry bedding. Contact a veterinarian if the goat is weak, shivering, or a kid.',
        'Low Battery' => 'Recharge or replace the collar battery soon to avoid losing health monitoring on this goat.',
        'Abnormal Movement' => 'Check the goat in person for injury, lameness, or illness. Isolate it and contact a veterinarian if it will not stand or eat.',
    ];

    public static function recommendationFor(string $alertType, ?string $severity = null): string
    {
        $level = strtolower($severity ?? '');
        $urgent = in_array($level, ['urgent', 'high', 'critical'], true);
        $warning = !$urgent && (in_array($level, ['warning', 'monitoring'], true) || str_contains(strtolower($alertType), 'warning'));
        $lookup = str_replace(' Warning', '', $alertType);
        if ($warning && isset(self::ADVICE[$lookup . ' Warning'])) {
            $lookup .= ' Warning';
        }
        $advice = self::ADVICE[$lookup]
            ?? 'Please check on the goat in person and contact a licensed veterinarian if symptoms persist or worsen.';

        if ($urgent) {
            $advice = 'Urgent: inspect the goat immediately and contact a veterinarian now. ' . $advice;
        } elseif ($warning) {
            $advice = 'Warning: promptly recheck the reading and monitor closely. ' . $advice;
        }

        return "Alert: {$alertType} – {$advice}";
    }
}
