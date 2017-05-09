<?php

namespace hypeJunction\Crumbs;

use ElggEntity;
use ElggGroup;
use ElggObject;
use ElggUser;

class Navigation {

	/**
	 * Rebuild crumbs
	 *
	 * @param string $hook   "prepare"
	 * @param string $type   "breadcrumbs"
	 * @param array  $return Crumbs
	 * @param array  $params Hook params
	 * @return array
	 */
	public static function prepare($hook, $type, $return, $params) {

		$segments = _elgg_services()->request->getUrlSegments();
		$identifier = array_shift($segments);

		switch ($identifier) {
			case 'groups' :
				switch ($segments[0]) {
					case 'all' :
					case 'profile' :
						return [];

					case 'activity' :
					case 'members' :
					case 'edit' :
					case 'requests' :
					case 'invite' :
						$guid = $segments[1];
						$group = get_entity($guid);
						if ($group) {
							return [
								[
									'title' => $group->getDisplayName(),
									'link' => $group->getURL(),
								],
								[
									'title' => elgg_echo("groups:{$segments[0]}"),
								],
							];
						}
						break;

					case 'member' :
					case 'owner' :
					case 'invitations' :
						$username = $segments[1];
						if (!$username) {
							$username = elgg_get_logged_in_user_entity()->username;
						}
						$user = get_user_by_username($username);
						if (!$user && is_numeric($username)) {
							$user = get_entity($username);
						}
						if ($user) {
							return [
								[
									'title' => $user->getDisplayName(),
									'link' => $user->getURL(),
								],
								[
									'title' => elgg_echo("crumb:groups:{$segments[0]}"),
									'link' => "groups/{$segments[0]}/{$user->username}",
								],
							];
						}
				}
				break;
		}

		$crumbs = self::getCrumbs($identifier, $segments);

		if (count($crumbs) > 1) {
			return $crumbs;
		}
	}

	public static function getCrumbs($identifier = '', array $segments = []) {

		$page = array_shift($segments);

		switch ($page) {

			case 'view' :
			case 'edit' :
				$guid = array_shift($segments);
				$entity = get_entity($guid);
				if ($entity) {
					$crumbs = self::getEntityCrumbs($entity, $identifier, $page);
				}
				break;

			case 'add' :
				$container_guid = array_shift($segments) ?: elgg_get_logged_in_user_guid();
				$container = get_entity($container_guid);
				if ($container) {
					$crumbs = self::getEntityCrumbs($container, $identifier, '');
				}
				if ($container instanceof ElggGroup) {
					$crumbs[] = [
						'title' => elgg_echo("crumbs:collection:$identifier"),
						'link' => "$identifier/group/$container->guid",
					];
				} else if ($container instanceof ElggUser) {
					$crumbs[] = [
						'title' => elgg_echo("crumbs:collection:$identifier"),
						'link' => "$identifier/owner/$container->username",
					];
				}
				$crumbs[] = [
					'title' => elgg_echo('crumbs:add'),
				];
				break;

			case 'owner' :
			case 'friends' :
				$username = array_shift($segments);
				if (!$username) {
					$username = elgg_get_logged_in_user_entity()->username;
				}
				$user = get_user_by_username($username);
				if (!$user && is_numeric($username)) {
					$user = get_entity($username);
				}
				if ($user) {
					$crumbs[] = [
						'title' => $user->getDisplayName(),
						'link' => $user->getURL(),
					];
					if ($page == 'friends') {
						$crumbs[] = [
							'title' => elgg_echo('friends'),
							'link' => "friends/$user->username",
						];
					}
				}
				$crumbs[] = [
					'title' => elgg_echo("crumbs:collection:$identifier"),
				];
				break;

			case 'group' :
				$guid = array_shift($segments);
				$group = get_entity($guid);
				if ($group) {
					$crumbs[] = [
						'title' => $group->getDisplayName(),
						'link' => $group->getURL(),
					];
				}

				$subpage = array_shift($segments);

				switch ($subpage) {

					case 'archive' :
						$crumbs[] = [
							'title' => elgg_echo("crumbs:collection:$identifier"),
							'link' => "$identifier/group/$guid",
						];
						$crumbs[] = [
							'title' => elgg_echo('crumbs:archive'),
							'link' => "$identifier/group/$guid/archive",
						];
						break;

					default :
						$crumbs[] = [
							'title' => elgg_echo("crumbs:collection:$identifier"),
						];
						break;
				}

				break;

			case 'reply' :
				$subpage = array_shift($segments);
				$guid = array_shift($segments);
				$entity = get_entity($guid);
				if ($entity) {
					$crumbs = [];
					$parent = $entity->getContainerEntity();
					if ($parent instanceof ElggObject) {
						$crumbs = self::getEntityCrumbs($parent, $identifier, '');
					} else {
						$crumbs = self::getEntityCrumbs($entity, $identifier, '');
					}
					if ($subpage == 'edit') {
						$crumbs[] = [
							'title' => $entity->getDisplayName() ?: elgg_echo("crumb:object:{$entity->getSubtype()}"),
							'link' => $entity->getURL(),
						];
						$crumbs[] = [
							'title' => elgg_echo("crumbs:$page"),
						];
					} else {
						$crumbs[] = [
							'title' => $entity->getDisplayName(),
						];
					}
				}
				break;
		}

		return $crumbs;
	}

	public function getEntityCrumbs(ElggEntity $entity, $identifier = '', $subpage = '') {
		$crumbs = [];

		if (!$entity instanceof ElggUser && !$entity instanceof ElggGroup) {
			$container = $entity->getContainerEntity();
			if ($container instanceof ElggEntity) {
				$crumbs[] = [
					'title' => $container->getDisplayName(),
					'link' => $container->getURL(),
				];
			}
			if ($container instanceof ElggGroup) {
				$crumbs[] = [
					'title' => elgg_echo("crumbs:collection:$identifier"),
					'link' => "$identifier/group/$container->guid",
				];
			} else if ($container instanceof ElggUser) {
				$crumbs[] = [
					'title' => elgg_echo("crumbs:collection:$identifier"),
					'link' => "$identifier/owner/$container->username",
				];
			}

			if ($entity->parent_guid) {
				$parents = array();
				$parent = get_entity($entity->parent_guid);
				while ($parent) {
					array_push($parents, $parent);
					$parent = get_entity($parent->parent_guid);
				}
				while ($parents) {
					$parent = array_pop($parents);
					$crumbs[] = [
						'title' => $parent->title,
						'link' => $parent->getURL(),
					];
				}
			}
		}

		if ($subpage !== 'view' && $subpage !== 'profile') {
			$crumbs[] = [
				'title' => $entity->getDisplayName(),
				'link' => $entity->getURL(),
			];
			if ($subpage) {
				$crumbs[] = [
					'title' => elgg_echo("crumbs:$subpage"),
				];
			}
		} else {
			$crumbs[] = [
				'title' => $entity->getDisplayName(),
			];
		}

		return $crumbs;
	}

}
