<?php

namespace Realtime\Client;

use Thruway\Message\Message;
use Thruway\Message\PublishMessage;
use Thruway\Event\MessageEvent;
use Thruway\Logging\Logger;
use Illuminate\Support\Str;
use App\Util\Constants;
use App\Util\Util;
use MongoDB\Operation\FindOneAndUpdate;
use MongoDB\Model\BSONArray;

class GroupManager extends AbstractManager {

    /**
     * Constructor
     */
    public function __construct($db, $realm = 'group') {
        parent::__construct($db, $realm);
    }

    public function initModule(\Thruway\Peer\RouterInterface $router, \React\EventLoop\LoopInterface $loop) {
        parent::initModule($router, $loop);
    }

    public function onSessionStart($session, $transport) {
        parent::onSessionStart($session, $transport);
    }

    public function onSessionJoin($args, $kwArgs, $options) {
        parent::onSessionJoin($args, $kwArgs, $options);
    }


    protected function validateContent($content) {
        return true;
    }

    public function handlePublishMessage(MessageEvent $event) {
        $session = $event->session;
        $message = $event->message;

        $json = $message->jsonSerialize();
        $topic = $json[3];
        $content = $json[4];
    }

    /**
     * From the mobile app the user will call this method to fetch groups user belong to
     * 
     * @param type $args
     */
    public function callGroupFetchUserGroupsBelong($args) {
        $user_id = $args[0];
        $user = $this->db->users->findOne([
            'user_id' => $user_id
        ]);
		
        if (!$user || !isset($user->groups_belong) || count($user->groups_belong) == 0) {
            return $this->serialized(array());
        }

        $query = ['$or' => []];
        foreach ($user->groups_belong as $group_name) {
            $query['$or'][] = ['name' => $group_name];
        }

        $cursor = $this->db->groups->find($query,
                ['projection' => ['_id' => 0]]);

        $groups = array();
        foreach ($cursor as $group) {
            $groups[] = $group;
        }

        return $this->serialized($groups);
    }

    /**
     * From the mobile app the user will call this method to create a group
     * 
     * @param type $args
     */
    public function callGroupCreate($args) {
        $user_id = $args[0];
        $group = $args[1]; //group object

        $group->created_by = $user_id;
		$group->created_at = Util::millitime();		
		$group->updated_at = Util::millitime();
		
        if (!is_array($group->member_ids)) {
            $group->member_ids = array();
        }

        if (!is_array($group->admin_ids)) {
            $group->admin_ids = array();
        }

        if (!in_array($user_id, $group->member_ids)) {
            $group->member_ids[] = $user_id;
        }

        if (!in_array($user_id, $group->admin_ids)) {
            $group->admin_ids[] = $user_id;
        }

        if (!empty($group->photo_base64)) {
            $group->photo_url = Util::uploadBase64($group->photo_base64,
                            Constants::DEFAULT_UPLOAD_GROUP_PROFILE_DIR,
                            $group->name,
                            $group->photo_file_extension);
        }

        $group = $this->upsertFields($this->db->groups, $group, 'name', array('photo_base64'), true);

        //add the group in the user document
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $user_id], ['$addToSet' => ['groups_belong' => $group->name]]
        );

		return $this->serialized($group);
    }

    /**
     * From the mobile app the user (admin) will call this method to edit a group
     * 
     * @param type $args
     */
    public function callGroupEdit($args) {
        $user_id = $args[0]; //must be an admin
        $group = $args[1]; //group object
		$subscribed_topic = $args[2];
		
        $old_group_name = $group->name;
        $old_status_text = $group->status_text;

        $found = $this->db->groups->findOne(
                ['name' => $group->name]
        );

        if (!$found) {
            return $this->error("Group $group->name not found. ");
        }
		
		//$found->admin_ids is BSONArray
		
		$admin_ids = $found->admin_ids->getArrayCopy();
		
        if (!is_array($admin_ids) || !in_array($user_id, $admin_ids)) {
            return $this->error("Not allowed! $user_id is not an admin in $group->name group.");
        }

        if (empty($group->name)) {
            return $this->error("No group name. ");
        }

        if (!empty($group->photo_base64)) {
            $group->photo_url = Util::uploadBase64($group->photo_base64,
                            Constants::DEFAULT_UPLOAD_GROUP_PROFILE_DIR,
                            $group->name,
                            $group->photo_file_extension);
        }

		$group->updated_at = Util::millitime();
		
        $setOp = ['$set' => []];
        foreach ($group as $key => $value) {
            if ($key == 'name') {
                continue; //skip
            }
            $setOp['$set'][$key] = $value;
        }

        if (count($setOp) == 0) {
            return $this->error("Group $group->name not edited. ");
        }

        $group = $this->db->groups->findOneAndUpdate(
                ['name' => $group->name],
				$setOp,
				['projection' => ['_id' => 0], 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );

        $group_edited = [
            'edited_by' => $user_id,
            'old_group_name' => $old_group_name,
            'new_group_name' => $group->name,
            'old_status_text' => $old_status_text,
            'new_status_text' => $group->status_text
        ];
        //publish GroupEdited event to the subscribers

        $group = json_encode($group);
        $group_edited = json_encode($group_edited);

        $this->session->publish($subscribed_topic, [$group, $group_edited], [], ["acknowledge" => true])
                ->then(function () use($subscribed_topic) {
                    Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                }, function ($error) use($subscribed_topic) {
                    // publish failed
                    Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                }
        );

        return $this->serialized(Constants::ACTION_SUCCESSFUL);
    }

    /**
     * From the mobile app the user call this method to exit a group.
     * Then this GroupManger will publish the event to all the members in the
     * group using the topic url provided as one of the arguments
     * 
     * ie
     * com.ukonectinfo.group.member.left.<group name>
     * 
     * all app users subscribed to the above topic will be notified of the user exit
     * 
     * 
     * @param type $args
     */
    public function callGroupMemberLeave($args) {
        $member_user_id = $args[0];
        $group_name = $args[1];
        $subscribed_topic = $args[2]; //GroupManager will use this topic to broadcast GroupMemberLeft event to the subscribers of this topic (ie the group members themselves)
        //remove the group in the user document
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $member_user_id], ['$pull' => ['groups_belong' => $group_name]]
        );

        //remove the user in the group document
        $updateResult = $this->db->groups->updateOne(
                ['name' => $group_name], ['$pull' => ['member_ids' => $member_user_id]]
        );

        if ($updateResult->getModifiedCount()) {
            //publish GroupMemberLeft event to the subscribers
            $data = [
                'group_name' => $group_name,
                'member_id' => $member_user_id,
                'removed_by' => $member_user_id, //removed ownself
            ];

            $data = json_encode($data);

            $this->session->publish($subscribed_topic, [$data], [], ["acknowledge" => true])
                    ->then(function () use($subscribed_topic) {
                        Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                    }, function ($error) use($subscribed_topic) {
                        // publish failed
                        Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                    }
            );

            return $this->serialized(Constants::ACTION_SUCCESSFUL);
        } else {
            return $this->serialized(Constants::NOTHING_CHANGED);
        }
    }

    /**
     * A user can send this group link to another user to join his group.
     * The method thus creates the link and sends it to the requested user
     * via private chat. 
     * 
     * @param type $args
     */
    public function callGroupMemberSendLinkToJoin($args) {
        $gjrChat = $args[0]; //ChatMessage containing GroupJoinRequest object from the frontend
        $gjrTopic = $args[1];
        $excluded_user_session_id = isset($args[2])?$args[2] : ""; //exclude this session id - it is the session id of the person who sent the link. we only want to send this message to the other user
        $internal_cleint_session_id = $this->session->getSessionId(); //we will also exclude the internal client session id
        //send to the desired user

		if($gjrChat->from_user_id == $gjrChat->to_user_id){
			return $this->error("Invalid request! You cannot send group join request to yourself!");
		}

        $this->session->publish($gjrTopic, [$gjrChat], [], [
                    "acknowledge" => true,
                    'exclude' => [$excluded_user_session_id, $internal_cleint_session_id],
                        /* 'eligible' => $sessionIds */                        ]
                )
                ->then(function () use($gjrTopic) {
                    Logger::debug($this, "Publish Acknowledged - $gjrTopic\n");
                }, function ($error) use($gjrTopic) {
                    // publish failed
                    Logger::debug($this, "Publish Error {$error}\n - $gjrTopic\n");
                }
        );

        return $this->serialized(Constants::ACTION_SUCCESSFUL);
    }

    /**
     * When the requested user clicks the group-join-link in his private chat
     * this method is called. 
     * Then this GroupManger will publish the event to all the members in the
     * group using the topic url provided as one of the arguments
     * 
     * ie
     * com.ukonectinfo.group.member.added.<group name>
     * 
     * all app users subscribed to the above topic will be notified of the user added
     * @param type $args
     */
    public function callGroupMemberJoinByLink($args) {
        $would_be_member_user_id = $args[0];
        $invitee_user_id = $args[1]; //the one who invited the would-be-member
        $group_name = $args[2];
        $subscribed_topic = $args[3]; //GroupManager will use this topic to broadcast GroupMemberAdded event to the subscribers of this topic (ie the group members themselves)
        //add the group in the user document
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $would_be_member_user_id], ['$addToSet' => ['groups_belong' => $group_name]]
        );

        //add the user in the group document
        $updateResult = $this->db->groups->updateOne(
                ['name' => $group_name], ['$addToSet' => ['member_ids' => $would_be_member_user_id]]
        );

        if ($updateResult->getModifiedCount()) {
            //publish GroupMemberAdded event to the subscribers
            $data = [
                'group_name' => $group_name,
                'member_id' => $would_be_member_user_id,
                'added_by' => $invitee_user_id,
            ];

            $data = json_encode($data);

            $this->session->publish($subscribed_topic, [$data], [], ["acknowledge" => true])
                    ->then(function () use($subscribed_topic) {
                        Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                    }, function ($error) use($subscribed_topic) {
                        // publish failed
                        Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                    }
            );

            return $this->serialized(Constants::ACTION_SUCCESSFUL);
        } else {
            return $this->serialized(Constants::NOTHING_CHANGED);
        }
    }

    /**
     * A group admin user can added a user directly by calling this method.
     * Then this GroupManger will publish the event to all the members in the
     * group using the topic url provided as one of the arguments
     * 
     * ie
     * com.ukonectinfo.group.member.added.<group name>
     * 
     * all app users subscribed to the above topic will be notified of the user added
     * @param type $args
     */
    public function callGroupAdminAddMember($args) {

        $admin_user_id = $args[0]; // we must verify he has admin priviledge
        $new_member_user_id = $args[1];
        $group_name = $args[2];
        $subscribed_topic = $args[3]; //GroupManager will use this topic to broadcast GroupMemberAdded event to the subscribers of this topic (ie the group members themselves)
        
		$adminGroup = $this->db->groups->findOne(
				['name' => $group_name,
				'admin_ids' => $admin_user_id// ie if $admin_user_id is in admin_ids array
				],
				['projection' => ['_id' => 0]]);
				
		if(!$adminGroup){
			return $this->error("Not allowed! $admin_user_id must be an admin in $group_name group to perform this operation!");
		}			
		
		//add the group in the user document
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $new_member_user_id], ['$addToSet' => ['groups_belong' => $group_name]]
        );

        //add the user in the group document
        $updateResult = $this->db->groups->updateOne(
                ['name' => $group_name], ['$addToSet' => ['member_ids' => $new_member_user_id]]
        );

        if ($updateResult->getModifiedCount()) {
            //publish GroupMemberAdded event to the subscribers
            $data = [
                'group_name' => $group_name,
                'member_id' => $new_member_user_id,
                'added_by' => $admin_user_id,
            ];

            $data = json_encode($data);

            $this->session->publish($subscribed_topic, [$data], [], ["acknowledge" => true])
                    ->then(function () use($subscribed_topic) {
                        Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                    }, function ($error) use($subscribed_topic) {
                        // publish failed
                        Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                    }
            );

            return $this->serialized(Constants::ACTION_SUCCESSFUL);
        } else {
            return $this->serialized(Constants::NOTHING_CHANGED);
        }
    }

    /**
     * A group admin user can remove a user by calling this method.
     * Then this GroupManger will publish the event to all the members in the
     * group using the topic url provided as one of the arguments
     * 
     * ie
     * com.ukonectinfo.group.member.removed.<group name>
     * 
     * all app users subscribed to the above topic will be notified of the user removed
     * @param type $args
     */
    public function callGroupAdminRemoveMember($args) {
        $admin_user_id = $args[0]; // we must verify he has admin priviledge
        $member_user_id = $args[1];
        $group_name = $args[2];
        $subscribed_topic = $args[3]; //GroupManager will use this topic to broadcast GroupMemberRemoved event to the subscribers of this topic (ie the group members themselves)
        
		$adminGroup = $this->db->groups->findOne(
				['name' => $group_name,
				'admin_ids' => $admin_user_id// ie if $admin_user_id is in admin_ids array
				],
				['projection' => ['_id' => 0]]);
				
		if(!$adminGroup){
			return $this->error("Not allowed! $admin_user_id must be an admin in $group_name group to perform this operation!");
		}			
		
		
		//remove the group in the user document
        $updateResult = $this->db->users->updateOne(
                ['user_id' => $member_user_id], ['$pull' => ['groups_belong' => $group_name]]
        );

        //remove the user in the group document
        $updateResult = $this->db->groups->updateOne(
                ['name' => $group_name], ['$pull' => ['member_ids' => $member_user_id]]
        );

        if ($updateResult->getModifiedCount()) {
            //publish GroupMemberRemoved event to the subscribers
            $data = [
                'group_name' => $group_name,
                'member_id' => $member_user_id,
                'removed_by' => $admin_user_id,
            ];

            $data = json_encode($data);

            $this->session->publish($subscribed_topic, [$data], [], ["acknowledge" => true])
                    ->then(function () use($subscribed_topic) {
                        Logger::debug($this, "Publish Acknowledged - $subscribed_topic\n");
                    }, function ($error) use($subscribed_topic) {
                        // publish failed
                        Logger::debug($this, "Publish Error {$error}\n - $subscribed_topic\n");
                    }
            );

            return $this->serialized(Constants::ACTION_SUCCESSFUL);
        } else {
            return $this->serialized(Constants::NOTHING_CHANGED);
        }
    }

    /**
     *
     * @return array
     */
    public function getSubscribedRealmEvents() {
        return [
            'PublishMessageEvent' => ['handlePublishMessage', 10],
        ];
    }

    public function registerCalls($session) {
        $session->register('com.ukonectinfo.backend.group.create', [$this, 'callGroupCreate']);
        $session->register('com.ukonectinfo.backend.group.edit', [$this, 'callGroupEdit']);
        $session->register('com.ukonectinfo.backend.group.member.leave', [$this, 'callGroupMemberLeave']);
        $session->register('com.ukonectinfo.backend.group.member.send.link.to.join', [$this, 'callGroupMemberSendLinkToJoin']);
        $session->register('com.ukonectinfo.backend.group.member.join.by.link', [$this, 'callGroupMemberJoinByLink']);
        $session->register('com.ukonectinfo.backend.group.admin.add.member', [$this, 'callGroupAdminAddMember']);
        $session->register('com.ukonectinfo.backend.group.admin.remove.member', [$this, 'callGroupAdminRemoveMember']);
        $session->register('com.ukonectinfo.backend.group.fetch.user.groups.belong', [$this, 'callGroupFetchUserGroupsBelong']);
    }

}
