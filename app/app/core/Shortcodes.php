<?php
namespace Altum;

/* Custom code */
class Shortcodes {
	private function should_use_default_shortcodes_fallback($referral_key = null) {
		/* Custom code: FC-2026-02-26: explicit default shortcode fallback trigger */
		$legacy_referral_slug = 'wpebe1grqr';
		$has_ref_query = isset($_GET['ref']) && trim((string) $_GET['ref']) !== '';
		$has_referral_cookie = isset($_COOKIE['referral']) && trim((string) $_COOKIE['referral']) !== '' && trim((string) $_COOKIE['referral']) !== $legacy_referral_slug;
		$has_referred_by_cookie = isset($_COOKIE['referred_by']) && trim((string) $_COOKIE['referred_by']) !== '' && trim((string) $_COOKIE['referred_by']) !== $legacy_referral_slug;

		if($has_ref_query || $has_referral_cookie || $has_referred_by_cookie) {
			return false;
		}

		return true;
		/* /Custom code: FC-2026-02-26 */
	}

	private function get_default_shortcodes_values() {
		/* Custom code: FC-2026-02-26: no-referral default shortcode values */
		$default_aff_link = 'https://forevercard.club/ddglabhlcn';

		return [
			'name' => 'Snježana & Stjepan Beloša',
			'email' => 'stjepan@belosa.info',
			'phone' => '+385911561596',
			'forever_id' => '360000760944',
			'aff_biolink' => '<a target="_blank" href="' . $default_aff_link . '">' . $default_aff_link . '</a>',
		];
		/* /Custom code: FC-2026-02-26 */
	}

	private function get_meta_value($meta, $keys = []) {
		foreach($keys as $key) {
			if(is_object($meta) && isset($meta->{$key}) && trim((string) $meta->{$key}) !== '') {
				return trim((string) $meta->{$key});
			}

			if(is_array($meta) && isset($meta[$key]) && trim((string) $meta[$key]) !== '') {
				return trim((string) $meta[$key]);
			}
		}

		return null;
	}

	private function get_nested_value($data, $keys = [], $depth = 0) {
		if($depth > 6 || $data === null) {
			return null;
		}

		if(is_object($data)) {
			$data = (array) $data;
		}

		if(is_array($data)) {
			foreach($keys as $key) {
				if(isset($data[$key]) && !is_array($data[$key]) && !is_object($data[$key]) && trim((string) $data[$key]) !== '') {
					return trim((string) $data[$key]);
				}
			}

			foreach($data as $value) {
				if(is_array($value) || is_object($value)) {
					$nested_value = $this->get_nested_value($value, $keys, $depth + 1);
					if($nested_value !== null) {
						return $nested_value;
					}
				}
			}
		}

		return null;
	}

	private function get_referral_user($referral_key = null) {
		/* Custom code: FC-2026-02-26: robust referral user resolution for shortcodes */
		$legacy_referral_slug = 'wpebe1grqr';
		$referral_candidates = [];

		if($referral_key && trim((string) $referral_key) !== $legacy_referral_slug) {
			$referral_candidates[] = trim((string) $referral_key);
		} else {
			if(!empty($_GET['ref'])) {
				$referral_candidates[] = query_clean($_GET['ref']);
			}

			if(!empty($_COOKIE['referral']) && trim((string) $_COOKIE['referral']) !== $legacy_referral_slug) {
				$referral_candidates[] = trim((string) $_COOKIE['referral']);
			}

			if(!empty($_COOKIE['referred_by']) && trim((string) $_COOKIE['referred_by']) !== $legacy_referral_slug) {
				$referral_candidates[] = trim((string) $_COOKIE['referred_by']);
			}

			/* Custom code: FC-2026-02-26: no hardcoded legacy fallback candidate */
			/* /Custom code: FC-2026-02-26 */
		}

		$normalized_candidates = [];
		foreach($referral_candidates as $candidate) {
			if(!$candidate) continue;

			$candidate = trim((string) $candidate);
			if($candidate === '') continue;

			$normalized_candidates[] = ltrim($candidate, '/');

			if(filter_var($candidate, FILTER_VALIDATE_URL)) {
				$candidate_path = parse_url($candidate, PHP_URL_PATH);
				if($candidate_path) {
					$normalized_candidates[] = ltrim($candidate_path, '/');
					$normalized_candidates[] = basename($candidate_path);
				}
			}
		}

		$normalized_candidates = array_values(array_unique(array_filter($normalized_candidates)));

		foreach($normalized_candidates as $candidate) {
			/* Custom code: FC-2026-02-26: support affiliate referral key resolution */
			$user = db()->where('referral_key', $candidate)->where('status', 1)->getOne('users');
			if($user) {
				return $user;
			}
			/* /Custom code: FC-2026-02-26 */

			$biolink = db()->where('url', $candidate)->where('type', 'biolink')->getOne('links', ['user_id']);

			if(!$biolink) {
				$biolink = db()->where('url', $candidate)->getOne('links', ['user_id']);
			}

			if($biolink) {
				$user = db()->where('user_id', $biolink->user_id)->where('status', 1)->getOne('users');
				if($user) {
					return $user;
				}
			}
		}

		return null;
		/* /Custom code: FC-2026-02-26 */
	}

	public function display_shortcodes($content, $referral_key = null)
	{
		/* Custom code: FC-2026-02-26: robust blog shortcode parsing & replacement */
		if(!is_string($content) || $content === '') {
			return $content;
		}

		$content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$params = [
			'name' => $this->generate_shorcode('name', $referral_key),
			'email' => $this->generate_shorcode('email', $referral_key),
			'phone' => $this->generate_shorcode('phone', $referral_key),
			'forever_id' => $this->generate_shorcode('forever_id', $referral_key),
			'aff_biolink' => $this->generate_shorcode('aff_biolink', $referral_key),
		];

		$content = str_replace(['\\[', '\\]'], ['[', ']'], $content);

		$content = preg_replace_callback('/\[\s*(name|email|phone|forever_id|aff_biolink)\s*\]/i', function($matches) use ($params) {
			$key = strtolower($matches[1]);
			return isset($params[$key]) && $params[$key] !== null ? $params[$key] : '';
		}, $content);

		return $content;
		/* /Custom code: FC-2026-02-26 */
	}

	function generate_shorcode($shortcode, $referral_key = null) {
		if (!in_array($shortcode, ['name', 'email', 'phone', 'forever_id', 'aff_biolink'])) {
			return;
		}

		$default_shortcodes_values = $this->get_default_shortcodes_values();

		/* Custom code: FC-2026-02-26: no-referral shortcode fallback data */
		if($this->should_use_default_shortcodes_fallback($referral_key)) {
			if(isset($default_shortcodes_values[$shortcode])) {
				return $default_shortcodes_values[$shortcode];
			}
		}
		/* /Custom code: FC-2026-02-26 */

		/* Custom code: FC-2026-02-26: resolve referral user without plan restriction */
		$user = $this->get_referral_user($referral_key);
		/* /Custom code: FC-2026-02-26 */

		if ($user) {
			$preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences ?? '{}') : ($user->preferences ?? (object) []);
			$meta = $preferences->meta ?? (object) [];
			$billing = is_string($user->billing ?? null) ? json_decode($user->billing ?? '{}') : ($user->billing ?? (object) []);

			if(is_string($meta)) {
				$decoded_meta = json_decode($meta);
				$meta = $decoded_meta ?: (object) [];
			}

			if(is_string($billing)) {
				$decoded_billing = json_decode($billing);
				$billing = $decoded_billing ?: (object) [];
			}

			switch ($shortcode) {
				case 'name':
					/* Custom code: FC-2026-02-26: shortcode name fallback */
					$user_name = trim((string) ($user->name ?? ''));
					if($user_name !== '') {
						return $user_name;
					}

					$meta_name = $this->get_meta_value($meta, ['name', 'full_name']);
					if($meta_name) {
						return $meta_name;
					}

					$first_name = $this->get_meta_value($meta, ['first_name', 'firstname']);
					$last_name = $this->get_meta_value($meta, ['last_name', 'lastname']);
					$combined_name = trim((string) ($first_name . ' ' . $last_name));

					if($combined_name !== '') {
						return $combined_name;
					}
					/* /Custom code: FC-2026-02-26 */
					break;
				
				case 'email':
					/* Custom code: FC-2026-02-26: shortcode email fallback */
					$email = trim((string) ($user->email ?? ''));
					if($email !== '') {
						return $email;
					}

					$meta_email = $this->get_meta_value($meta, ['email', 'user_email']);
					if($meta_email) {
						return $meta_email;
					}
					/* /Custom code: FC-2026-02-26 */
					break;

				case 'phone':
					/* Custom code: FC-2026-02-26: shortcode phone fallback */
					$phone = $this->get_meta_value($meta, ['phone', 'phone_number', 'mobile', 'telephone']);
					if(!$phone) {
						$phone = $this->get_nested_value($preferences, ['phone', 'phone_number', 'mobile', 'telephone', 'tel']);
					}
					/* Custom code: FC-2026-02-26: shortcode phone fallback from billing data */
					if(!$phone) {
						$phone = $this->get_meta_value($billing, ['phone', 'phone_number', 'mobile', 'telephone', 'tel']);
					}
					if(!$phone) {
						$phone = $this->get_nested_value($billing, ['phone', 'phone_number', 'mobile', 'telephone', 'tel']);
					}
					/* /Custom code: FC-2026-02-26 */
					/* Custom code: FC-2026-02-26: shortcode phone fallback from vCard settings */
					if(!$phone && isset($user->user_id)) {
						$vcard_map = db()->where('user_id', $user->user_id)->getOne('users_vcards', ['vcard_id']);

						if($vcard_map && !empty($vcard_map->vcard_id)) {
							$vcard_link = db()->where('link_id', $vcard_map->vcard_id)->where('type', 'vcard')->getOne('links', ['settings']);

							if($vcard_link && !empty($vcard_link->settings)) {
								$vcard_settings = is_string($vcard_link->settings) ? json_decode($vcard_link->settings) : $vcard_link->settings;

								if($vcard_settings) {
									$vcard_phone_numbers = $vcard_settings->vcard_phone_numbers ?? [];

									if(is_array($vcard_phone_numbers) && !empty($vcard_phone_numbers)) {
										foreach($vcard_phone_numbers as $vcard_phone_number) {
											if(is_string($vcard_phone_number) && trim($vcard_phone_number) !== '') {
												$phone = trim($vcard_phone_number);
												break;
											}

											if(is_object($vcard_phone_number) && !empty($vcard_phone_number->value) && trim((string) $vcard_phone_number->value) !== '') {
												$phone = trim((string) $vcard_phone_number->value);
												break;
											}
										}
									}
								}
							}
						}
					}
					/* /Custom code: FC-2026-02-26 */
					if(!$phone && isset($user->phone) && trim((string) $user->phone) !== '') {
						$phone = trim((string) $user->phone);
					}
					if($phone) {
						return $phone;
					}
					/* /Custom code: FC-2026-02-26 */
					break;
					
				case 'forever_id':					
						/* Custom code: FC-2026-02-26: shortcode forever id fallback */
						$forever_id = $this->get_meta_value($meta, ['foreverId', 'forever_id', 'foreverID', 'meta_foreverId']);
						if($forever_id) {
							return $forever_id;
						}
						/* /Custom code: FC-2026-02-26 */
						break;

				case 'aff_biolink':				
					/* Custom code: FC-2026-02-26: shortcode aff_biolink robust lookup */
					$biolink = null;
					$biolink_map = db()->where('user_id', $user->user_id)->getOne('users_biolinks', ['biolink_id']);

					if($biolink_map && isset($biolink_map->biolink_id)) {
						$biolink = db()->where('link_id', $biolink_map->biolink_id)->where('type', 'biolink')->getOne('links', ['url']);
					}

					if(!$biolink) {
						$biolink = db()->where('user_id', $user->user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['url']);
					}

					if ($biolink && !empty($biolink->url)) {					
						$biolink_url = SITE_URL . ltrim($biolink->url, '/');
						return '<a target="_blank" href="' . $biolink_url .'">' . $biolink_url . '</a>';
					}
					/* /Custom code: FC-2026-02-26 */

					break;
			}			
		}

		/* Custom code: FC-2026-02-26: safety fallback when referral cookies are present but user data cannot be resolved */
		if(isset($default_shortcodes_values[$shortcode])) {
			return $default_shortcodes_values[$shortcode];
		}
		/* /Custom code: FC-2026-02-26 */
	}

}
/* Custom code */
