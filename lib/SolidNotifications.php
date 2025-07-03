<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\SolidPubSub;
	use Pdsinterop\Solid\SolidNotifications\SolidNotificationsInterface;

	class SolidNotifications implements SolidNotificationsInterface
	{
		private $notifications;
		public function __construct() {
			$pubsub = PUBSUB_SERVER;
			if ($pubsub) {
				$notifications[] = new SolidPubSub($pubsub);
			}

			$this->notifications = $notifications;
		}

		public function send($path, $type) {
			foreach ($this->notifications as $notification) {
				$notification->send($path, $type);
			}
		}
	}
