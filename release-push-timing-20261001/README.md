# FCC push notification delivery, 1 October 2026

The daily limit deferred daytime messages to 08:00 the following day. Quiet hours then released separate pushes at once. The service worker also replaced every title and body with a generic Croatian message.

The six files in payload were published at 12:51 UTC after verifying their production baselines and making a private source and pending queue backup. The existing verified full application and database backup remains available. No notification history, contacts, subscriptions or learning progress were deleted.

1. Removed the daily cap that deferred notifications to tomorrow. Ordinary bursts use a two minute device cooldown. Due daily, CC, conversation and event reminders bypass that cooldown, while quiet hours from 21:00 to 08:00 in Europe/Zagreb remain in force.
2. Eligible simultaneous items produce one request per subscription. Each inbox item retains its own read state, permissions, expiry and delivery result. Every item is validated before grouping. Provider retries retain the existing three attempt limit.
3. Public type codes and counts produce localized notification titles and explanations in Croatian, English, Slovenian, German and Spanish. Stored names, amounts and arbitrary notification text stay off the lock screen. Existing explicit CC detail preferences are honored for a single CC notice.
4. Added an independent sound preference, defaulting to platform sound. Muted notices omit vibration; enabled notices request one short vibration when supported. Custom tones and notification volume remain controlled by the platform.
5. Corrected inbox labels for contact and guest events and displayed timestamps in Zagreb time. Updated safe notification destinations and activated the compatible service worker update without waiting for all tabs to close.
6. Requeued 13 existing deliveries that the previous cap deferred to tomorrow morning. Production accepted all 13 through five device batches, without failures.

Validation: 55 database and transport checks, 168 service worker and presentation checks, 15 browser connection checks and 45 calendar checks passed, 283 in total. The calendar fixture expects Croatian, so its runner explicitly selected hr. PHP and JavaScript syntax checks passed. The settings page was checked at 360, 390 and 430 pixels without horizontal overflow. All six production file hashes were verified.

The test transport was used only locally. A final real test was requested through the signed in user's existing self test control. Provider acceptance is recorded separately from actual display or audible sound on a physical phone.

The manifest contains the production baseline and resulting SHA256 hashes. Deploy only payload files from this package. The surrounding release checkout is intentionally not the production application baseline. For rollback, restore the corresponding files from the private source backup recorded in publish-result.json. The queue snapshot contains scheduling metadata, and all existing inbox rows were preserved.

Final production check at 12:56:12 UTC: both of the signed in user’s connected devices have accepted test deliveries, the pending queue is empty, the transport is enabled and the worker heartbeat is current. The original minute worker was restored and verified. Actual display and sound on the physical phone cannot be confirmed remotely.
