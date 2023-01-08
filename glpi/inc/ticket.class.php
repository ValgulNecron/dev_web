<?php
/*
 * @version $Id: ticket.class.php 14762 2011-06-24 12:36:26Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Tracking class
class Ticket extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   protected $forward_entity_to = array('TicketValidation');

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   var $hardwaredatas = NULL;
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   var $computerfound = 0;

   /// Users by type
   protected $users = array();
   /// Groups by type
   protected $groups = array();

   // Request type
   const INCIDENT_TYPE = 1;
   // Demand type
   const DEMAND_TYPE   = 2;

   // Requester
   const REQUESTER = 1;
   // Assign
   const ASSIGN = 2;
   // Observer
   const OBSERVER = 3;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][5];
      }
      return $LANG['job'][38];
   }


   function canCreate() {
      return haveRight('create_ticket', 1);
   }


   function canUpdate() {

      return haveRight('update_ticket', 1)
             || haveRight('create_ticket', 1)
             || haveRight('assign_ticket', 1)
             || haveRight('steal_ticket', 1);
   }


   function canView() {
      return true;
   }


   /**
    * Is the current user have right to show the current ticket ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }

      return (haveRight("show_all_ticket","1")
              || $this->isUser(self::REQUESTER,getLoginUserID())
              || $this->isUser(self::OBSERVER,getLoginUserID())
              || (haveRight("show_group_ticket",'1')
                  && isset($_SESSION["glpigroups"])
                  && ($this->haveAGroup(self::REQUESTER,$_SESSION["glpigroups"])
                     || $this->haveAGroup(self::OBSERVER,$_SESSION["glpigroups"])))
              || (haveRight("show_assign_ticket",'1')
                  && ($this->isUser(self::ASSIGN,getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && $this->haveAGroup(self::ASSIGN,$_SESSION["glpigroups"]))
                      || (haveRight('assign_ticket',1) && $this->fields["status"]=='new')
                     )
                 )
              || (haveRight('validate_ticket','1') && TicketValidation::canValidate($this->fields["id"]))
             );
   }


   /**
    * Is the current user have right to solve the current ticket ?
    *
    * @return boolean
   **/
   function canSolve() {
      /// TODO block solution edition on closed status ?
      return ((haveRight("update_ticket","1")
               || $this->isUser(self::ASSIGN, getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $this->haveAGroup(self::ASSIGN, $_SESSION["glpigroups"])))
              && self::isAllowedStatus($this->fields['status'], 'solved'));
   }


   /**
    * Is the current user have right to approve solution of the current ticket ?
    *
    * @return boolean
   **/
   function canApprove() {

      return ($this->fields["users_id_recipient"] === getLoginUserID()
              || $this->isUser(self::REQUESTER, getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(self::REQUESTER, $_SESSION["glpigroups"])));
   }


   /**
    * Is a user linked to the ticket ?
    *
    * @param $type type to search (see constants)
    * @param $users_id integer user ID
    *
    * @return boolean
   **/
   function isUser($type, $users_id) {

      if (isset($this->users[$type]) && isset($this->users[$type][$users_id])) {
         return true;
      }

      return false;
   }


   /**
    * get users linked to a ticket
    *
    * @param $type type to search (see constants)
    *
    * @return array
   **/
   function getUsers($type) {

      if (isset($this->users[$type])) {
         return $this->users[$type];
      }

      return array();
   }


   /**
    * get groups linked to a ticket
    *
    * @param $type type to search (see constants)
    *
    * @return array
   **/
   function getGroups($type) {

      if (isset($this->groups[$type])) {
         return $this->groups[$type];
      }

      return array();
   }


   /**
    * count users linked to tickets by type or global
    *
    * @param $type type to search (see constants) / 0 for all
    *
    * @return integer
   **/
   function countUsers($type=0) {

      if ($type>0) {
         if (isset($this->users[$type])) {
            return count($this->users[$type]);
         }

      } else {
         if (count($this->users)) {
            $count = 0;
            foreach ($this->users as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }


   /**
    * count groups linked to tickets by type or global
    *
    * @param $type type to search (see constants) / 0 for all
    *
    * @return integer
   **/
   function countGroups($type=0) {

      if ($type>0) {
         if (isset($this->groups[$type])) {
            return count($this->groups[$type]);
         }

      } else {
         if (count($this->groups)) {
            $count = 0;
            foreach ($this->groups as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }


   /**
    * Is a group linked to the ticket ?
    *
    * @param $type type to search (see constants)
    * @param $groups_id integer group ID
    *
    * @return boolean
   **/
   function isGroup($type, $groups_id) {

      if (isset($this->groups[$type]) && isset($this->groups[$type][$groups_id])) {
         return true;
      }

      return false;
   }


   /**
    * Is a group linked to the ticket ?
    *
    * @param $type type to search (see constants)
    * @param $groups array of group ID
    *
    * @return boolean
   **/
   function haveAGroup($type, $groups) {

      if (is_array($groups) && count($groups)) {
         foreach ($groups as $groups_id) {
            if (isset($this->groups[$type]) && isset($this->groups[$type][$groups_id])) {
               return true;
            }
         }
      }
      return false;
   }


   /**
    * Delete SLA for the ticket
    *
    * @param $id ID of the ticket
    *
    * @return boolean
   **/
   function deleteSLA($id) {

      $input['slas_id']               = 0;
      $input['slalevels_id']          = 0;
      $input['sla_wainting_duration'] = 0;
      $input['id']                    = $id;
      return $this->update($input);
   }


   /**
    * Is the current user have right to create the current ticket ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return haveRight('create_ticket', '1');
   }


   /**
    * Is the current user have right to update the current ticket ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }

      if ($this->numberOfFollowups()==0  && $this->numberOfTasks()==0
            && $this->isUser(self::REQUESTER,getLoginUserID())) {
         return true;
      }

      return $this->canUpdate();
   }


   /**
    * Is the current user have right to delete the current ticket ?
    *
    * @return boolean
   **/
   function canDeleteItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return haveRight('delete_ticket', '1');
   }


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete',$this);
      return true;
   }


   function post_getFromDB () {

      $this->groups = Group_Ticket::getTicketGroups($this->fields['id']);
      $this->users  = Ticket_User::getTicketUsers($this->fields['id']);
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      if ($this->fields['id'] > 0) {
         if (haveRight('observe_ticket','1')) {
            $ong[1] = $LANG['mailing'][141];
         }
         if (haveRight('create_validation','1') ||haveRight('validate_ticket','1')) {
            $ong[7] = $LANG['validation'][8];
         }
         if (haveRight('observe_ticket','1')) {
            $ong[2] = $LANG['mailing'][142];
         }
         $ong[3] = $LANG['job'][47];
         $ong[4] = $LANG['jobresolution'][2];
         // enquete si statut clos
         if ($this->fields['status'] == 'closed') {
            $ong[10] = $LANG['satisfaction'][0];
         }
         $ong[5] = $LANG['Menu'][27];
         $ong[6] = $LANG['title'][38];
         if (haveRight('observe_ticket','1')) {
            $ong[8] = $LANG['Menu'][13];
         }

      //   $ong['no_all_tab'] = true;
      } else {
         $ong[1] = $LANG['job'][13];
      }

      return $ong;
   }


   /**
    * Retrieve an item from the database with datas associated (hardwares)
    *
    * @param $ID ID of the item to get
    * @param $purecontent boolean : true : nothing change / false : convert to HTML display
    *
    * @return true if succeed else false
   **/
   function getFromDBwithData ($ID, $purecontent) {
      global $DB, $LANG;

      if ($this->getFromDB($ID)) {
         if (!$purecontent) {
            $this->fields["content"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",
                                                          $this->fields["content"]));
         }
         $this->getHardwareData();
         return true;
      }
      return false;
   }


   /**
    * Retrieve data of the hardware linked to the ticket if exists
    *
    * @return nothing : set computerfound to 1 if founded
   **/
   function getHardwareData() {

      if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
         $item = new $this->fields["itemtype"]();
         if ($item->getFromDB($this->fields["items_id"])) {
            $this->hardwaredatas=$item;
         }

      } else {
         $this->hardwaredatas=NULL;
      }
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            $querydel = "DELETE
                         FROM `glpi_ticketplannings`
                         WHERE `tickettasks_id` = '".$data['id']."'";
            $DB->query($querydel);
         }
      }
      $query1 = "DELETE
                 FROM `glpi_tickettasks`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_ticketvalidations`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_ticketfollowups`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_tickets_tickets`
                 WHERE `tickets_id_1` = '".$this->fields['id']."'
                     OR `tickets_id_2` = '".$this->fields['id']."'";
      $DB->query($query1);

      $tu = new Ticket_User();
      $tu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gt = new Group_Ticket();
      $gt->cleanDBonItemDelete($this->getType(), $this->fields['id']);


   }


   function prepareInputForUpdate($input) {
      global $LANG, $CFG_GLPI;

      // Get ticket : need for comparison
      $this->getFromDB($input['id']);

      if (isset($input["date"]) && empty($input["date"])) {
         unset($input["date"]);
      }

      if (isset($input["closedate"]) && empty($input["closedate"])) {
         unset($input["closedate"]);
      }

      if (isset($input["solvedate"]) && empty($input["solvedate"])) {
         unset($input["solvedate"]);
      }

      // check mandatory fields
      if ($CFG_GLPI["is_ticket_title_mandatory"] && isset($input['name']) ) {
         $title = trim($input['name']);
         if (empty($title)) {
            addMessageAfterRedirect($LANG['tracking'][6], false, ERROR);
            unset($input['name']);
         }
      }

      if ($CFG_GLPI["is_ticket_content_mandatory"] && isset($input['content'])) {
         $content = trim($input['content']);
         if (empty($content)) {
            addMessageAfterRedirect($LANG['tracking'][7], false, ERROR);
            unset($input['content']);
         }
      }

      // Security checks
      if (is_numeric(getLoginUserID(false)) && !haveRight("assign_ticket","1")) {
         if (isset($input["_ticket_assign"])
             && isset($input['_ticket_assign']['_type'])
             && $input['_ticket_assign']['_type'] == 'user') {

            // must own_ticket to grab a non assign ticket
            if ($this->countUsers(self::ASSIGN)==0) {
               if ((!haveRight("steal_ticket","1") && !haveRight("own_ticket","1"))
                   || !isset($input["_ticket_assign"]['users_id'])
                   || ($input["_ticket_assign"]['users_id'] != getLoginUserID())) {
                  unset($input["_ticket_assign"]);
               }

            } else {
               // Can not steal or can steal and not assign to me
               if (!haveRight("steal_ticket","1")
                   || !isset($input["_ticket_assign"]['users_id'])
                   || ($input["_ticket_assign"]['users_id'] != getLoginUserID())) {
                  unset($input["_ticket_assign"]);
               }
            }
         }

         // No supplier assign
         if (isset($input["suppliers_id_assign"])) {
            unset($input["suppliers_id_assign"]);
         }

         // No group
         if (isset($input["_ticket_assign"])
             && isset($input['_ticket_assign']['_type'])
             && $input['_ticket_assign']['_type'] == 'group') {
            unset($input["_ticket_assign"]);
         }
      }

      if (is_numeric(getLoginUserID(false)) && !haveRight("update_ticket","1")) {

         $allowed_fields = array('id');

         if ($this->canApprove() && isset($input["status"])) {
            $allowed_fields[] = 'status';
         }

         // for post-only with validate right
         if (TicketValidation::canValidate($this->fields['id']) || TicketValidation::canCreate()) {
            $allowed_fields[] = 'global_validation';
         }

         // Manage assign and steal right
         if (haveRight('assign_ticket',1) || haveRight('steal_ticket',1)) {
            $allowed_fields[] = '_ticket_assign';
         }
         if (haveRight('assign_ticket',1)) {
            $allowed_fields[] = 'suppliers_id_assign';
         }

         // Can only update initial fields if no followup or task already added
         if ($this->numberOfFollowups() == 0
             && $this->numberOfTasks() == 0
             && $this->isUser(self::REQUESTER,getLoginUserID())) {
            $allowed_fields[] = 'content';
            $allowed_fields[] = 'urgency';
            $allowed_fields[] = 'ticketcategories_id';
            $allowed_fields[] = 'itemtype';
            $allowed_fields[] = 'items_id';
            $allowed_fields[] = 'name';
         }

         if ($this->canSolve()) {
            $allowed_fields[] = 'ticketsolutiontypes_id';
            $allowed_fields[] = 'solution';
         }

         foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
               $ret[$field] = $input[$field];
            }
         }

         $input = $ret;
      }

      // Manage fields from auto update : map rule actions to standard ones
      if (isset($input['_auto_update'])) {
         if (isset($input['_users_id_assign'])) {
            $input['_ticket_assign']['_type']    = 'user';
            $input['_ticket_assign']['users_id'] = $input['_users_id_assign'];
         }
         if (isset($input['_users_id_requester'])) {
            $input['_ticket_requester']['_type']    = 'user';
            $input['_ticket_requester']['users_id'] = $input['_users_id_requester'];
         }
         if (isset($input['_groups_id_requester'])) {
            $input['_ticket_assign']['_type']    = 'group';
            $input['_ticket_assign']['users_id'] = $input['_groups_id_requester'];
         }
         if (isset($input['_groups_id_assign'])) {
            $input['_ticket_requester']['_type']    = 'group';
            $input['_ticket_requester']['users_id'] = $input['_groups_id_assign'];
         }
      }

      if (isset($input['_link'])) {
         $ticket_ticket = new Ticket_Ticket();
         if ($ticket_ticket->can(-1, 'w', $input['_link'])) {
            $ticket_ticket->add($input['_link']);
            $input['_forcenotif'] = true;
         }
      }

      if (isset($input['_ticket_requester'])) {
         if (isset($input['_ticket_requester']['_type'])) {
            $input['_ticket_requester']['type']       = self::REQUESTER;
            $input['_ticket_requester']['tickets_id'] = $input['id'];
            switch ($input['_ticket_requester']['_type']) {
               case "user" :
                  if (isset($input['_ticket_requester']['alternative_email'])
                      && $input['_ticket_requester']['alternative_email']
                      && !NotificationMail::isUserAddressValid($input['_ticket_requester']['alternative_email'])) {
                     addMessageAfterRedirect($LANG['mailing'][111].' : '.$LANG['mailing'][110],
                                             false, ERROR);
                     $input['_ticket_requester']['alternative_email'] = '';
                  }

                  if ((isset($input['_ticket_requester']['alternative_email'])
                       && $input['_ticket_requester']['alternative_email'])
                      || $input['_ticket_requester']['users_id']>0) {
                     $ticket_user = new Ticket_User();
                     if ($ticket_user->can(-1,'w',$input['_ticket_requester'])) {
                        $ticket_user->add($input['_ticket_requester']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;

               case "group" :
                  $group_ticket = new Group_Ticket();
                  if ($group_ticket->can(-1,'w',$input['_ticket_requester'])) {
                     $group_ticket->add($input['_ticket_requester']);
                     $input['_forcenotif'] = true;
                  }
                  break;
            }
         }
      }

      if (isset($input['_ticket_observer'])) {
         if (isset($input['_ticket_observer']['_type'])) {
            $input['_ticket_observer']['type']       = self::OBSERVER;
            $input['_ticket_observer']['tickets_id'] = $input['id'];

            switch ($input['_ticket_observer']['_type']) {
               case "user" :
                  if (isset($input['_ticket_observer']['alternative_email'])
                      && $input['_ticket_observer']['alternative_email']
                      && !NotificationMail::isUserAddressValid($input['_ticket_observer']['alternative_email'])) {
                     $input['_ticket_observer']['alternative_email'] = '';
                     addMessageAfterRedirect($LANG['mailing'][111].' : '.$LANG['mailing'][110],
                                             false, ERROR);
                  }
                  if ((isset($input['_ticket_observer']['alternative_email'])
                       && $input['_ticket_observer']['alternative_email'])
                      || $input['_ticket_observer']['users_id']>0) {
                     $ticket_user = new Ticket_User();
                     if ($ticket_user->can(-1,'w',$input['_ticket_observer'])) {
                        $ticket_user->add($input['_ticket_observer']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;

               case "group" :
                  $group_ticket = new Group_Ticket();
                  if ($group_ticket->can(-1,'w',$input['_ticket_observer'])) {
                     $group_ticket->add($input['_ticket_observer']);
                     $input['_forcenotif'] = true;
                  }
                  break;
            }
         }
      }

      if (isset($input['_ticket_assign'])) {
         if (isset($input['_ticket_assign']['_type'])) {
            $input['_ticket_assign']['type']       = self::ASSIGN;
            $input['_ticket_assign']['tickets_id'] = $input['id'];

            switch ($input['_ticket_assign']['_type']) {
               case "user" :
                  $ticket_user = new Ticket_User();
                  if ($ticket_user->can(-1,'w',$input['_ticket_assign'])) {
                     $ticket_user->add($input['_ticket_assign']);
                     $input['_forcenotif'] = true;
                     if ((!isset($input['status']) && $this->fields['status']=='new')
                         || (isset($input['status']) && $input['status'] == 'new')) {
                        $input['status'] = 'assign';
                     }
                  }
                  break;

               case "group" :
                  $group_ticket = new Group_Ticket();
                  if ($group_ticket->can(-1,'w',$input['_ticket_assign'])) {
                     $group_ticket->add($input['_ticket_assign']);
                     $input['_forcenotif'] = true;
                     if ((!isset($input['status']) && $this->fields['status']=='new')
                         || (isset($input['status']) && $input['status'] == 'new')) {
                        $input['status'] = 'assign';
                     }
                  }
                  break;
            }
         }
      }


      // set last updater when non auto update
      if (!isset($input['_auto_update']) && $lastupdater=getLoginUserID(true)) {
         $input['users_id_lastupdater'] = $lastupdater;
      }

      if (isset($input["items_id"])
          && $input["items_id"]>=0
          && isset($input["itemtype"])) {

         if (isset($this->fields['groups_id'])
             && $this->fields['groups_id'] == 0
             && (!isset($input['groups_id']) || $input['groups_id'] == 0)) {

            if ($input["itemtype"] && class_exists($input["itemtype"])) {
               $item = new $input["itemtype"]();
               $item->getFromDB($input["items_id"]);
               if ($item->isField('groups_id')) {
                  $input["groups_id"] = $item->getField('groups_id');
               }
            }
         }

      } else if (isset($input["itemtype"]) && empty($input["itemtype"])) {
         $input["items_id"]=0;

      } else {
         unset($input["items_id"]);
         unset($input["itemtype"]);
      }

      // Add document if needed
      $this->getFromDB($input["id"]); // entities_id field required
      if (!isset($input['_donotadddocs']) || !$input['_donotadddocs']) {
         $docadded = $this->addFiles($input["id"]);
      }
      /*
      if (count($docadded)>0) {
         $input["date_mod"]=$_SESSION["glpi_currenttime"];
         if ($CFG_GLPI["add_followup_on_update_ticket"]) {
            $input['_doc_added']=$docadded;
         }
      }
      */

      if (isset($input["document"]) && $input["document"]>0) {
         $doc = new Document();
         if ($doc->getFromDB($input["document"])) {
            $docitem = new Document_Item();
            if ($docitem->add(array('documents_id' => $input["document"],
                                    'itemtype'     => $this->getType(),
                                    'items_id'     => $input["id"]))) {
               // Force date_mod of tracking
               $input["date_mod"] = $_SESSION["glpi_currenttime"];
               $input['_doc_added'][] = $doc->fields["name"];
            }
         }
         unset($input["document"]);
      }

      //Action for send_validation rule
      if (isset($input["_add_validation"]) && $input["_add_validation"]>0) {
         $validation = new Ticketvalidation();
         $values['tickets_id']        = $input['id'];
         $values['users_id_validate'] = $input["_add_validation"];
         if (isset($input["_auto_update"])) {
            $values['_auto_update'] = true;
         }

         if ($validation->can(-1, 'w', $values)) {
            $validation->add($values);

            Event::log($this->fields['id'], "ticket", 4, "tracking",
                       $_SESSION["glpiname"]."  ".$LANG['log'][21]);
         }
      }
      if (isset($input["status"]) && $input["status"]!='solved' && $input["status"]!='closed') {
         $input['solvedate'] = 'NULL';
      }

      if (isset($input["status"]) && $input["status"]!='closed') {
         $input['closedate'] = 'NULL';
      }

      return $input;
   }


   function pre_updateInDB() {
      global $LANG, $CFG_GLPI;


      // Check dates change interval due to the fact that second are not displayed in form
      if (($key=array_search('date',$this->updates)) !== false
          && (substr($this->fields["date"],0,16) == substr($this->oldvalues['date'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['date']);
      }

      if (($key=array_search('closedate',$this->updates)) !== false
          && (substr($this->fields["closedate"],0,16) == substr($this->oldvalues['closedate'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['closedate']);
      }

      if (($key=array_search('due_date',$this->updates)) !== false
          && (substr($this->fields["due_date"],0,16) == substr($this->oldvalues['due_date'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['due_date']);
      }

      if (($key=array_search('solvedate',$this->updates)) !== false
          && (substr($this->fields["solvedate"],0,16) == substr($this->oldvalues['solvedate'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['solvedate']);
      }


      if ($this->fields['status'] == 'new') {
         if (in_array("suppliers_id_assign",$this->updates)
             && $this->input["suppliers_id_assign"]>0) {

            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[]           = 'status';
            }
            $this->fields['status'] = 'assign';
            $this->input['status']  = 'assign';
         }
      }

      // Setting a solution or solution type means the ticket is solved
      if ((in_array("ticketsolutiontypes_id",$this->updates)
            && $this->input["ticketsolutiontypes_id"] >0)
          || (in_array("solution",$this->updates) && !empty($this->input["solution"]))) {

         if (!in_array('status', $this->updates)) {
            $this->oldvalues['status'] = $this->fields['status'];
            $this->updates[]           = 'status';
         }

         $entitydata = new EntityData();
         if ($entitydata->getFromDB($this->fields['entities_id'])) {
            $autoclosedelay = $entitydata->getfield('autoclose_delay');
         } else {
            $autoclosedelay = -1;
         }
         // -1 = config
         if ($autoclosedelay == -1) {
            $autoclosedelay = $CFG_GLPI['autoclose_delay'];
         }
         // 0 = immediatly
         if ($autoclosedelay == 0) {
            $this->fields['status'] = 'closed';
            $this->input['status']  = 'closed';
         } else {
            $this->fields['status'] = 'solved';
            $this->input['status']  = 'solved';
         }
      }

      if (isset($this->input["status"])) {
         if ($this->input["status"] != 'waiting'
             && isset($this->input["suppliers_id_assign"])
             && $this->input["suppliers_id_assign"] == 0
             && $this->countUsers(self::ASSIGN) == 0
             && $this->countGroups(self::ASSIGN) == 0
             && $this->fields['status'] != 'closed'
             && $this->fields['status'] != 'solved') {

            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[] = 'status';
            }
            $this->fields['status'] = 'new';
         }

         if (in_array("status",$this->updates) && $this->input["status"]=="solved") {
            $this->updates[] = "solvedate";
            $this->oldvalues['solvedate'] = $this->fields["solvedate"];
            $this->fields["solvedate"] = $_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["solvedate"] < $this->fields["date"]) {
               $this->fields["solvedate"] = $this->fields["date"];
            }
         }

         if (in_array("status",$this->updates) && $this->input["status"]=="closed") {
            $this->updates[] = "closedate";
            $this->oldvalues['closedate'] = $this->fields["closedate"];
            $this->fields["closedate"] = $_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["closedate"] < $this->fields["date"]) {
               $this->fields["closedate"] = $this->fields["date"];
            }
            // Set solvedate to closedate
            if (empty($this->fields["solvedate"])) {
               $this->updates[] = "solvedate";
               $this->oldvalues['solvedate'] = $this->fields["solvedate"];
               $this->fields["solvedate"] = $this->fields["closedate"];
            }
         }
      }

      // check dates

      // check due_date (SLA)
      if ((in_array("date",$this->updates) || in_array("due_date",$this->updates))
          && !is_null($this->fields["due_date"])) { // Date set

         if ($this->fields["due_date"] < $this->fields["date"]) {
            addMessageAfterRedirect($LANG['tracking'][3].$this->fields["due_date"], false, ERROR);

            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('due_date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['due_date']);
            }
         }
      }

      // Status solved : check dates
      if ($this->fields["status"]=="solved"
          && (in_array("date",$this->updates) || in_array("solvedate",$this->updates))) {

         // Invalid dates : no change
         // solvedate must be > create date
         if ($this->fields["solvedate"] < $this->fields["date"]) {
            addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);

            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('solvedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['solvedate']);
            }
          }
      }

      // Status close : check dates
      if ($this->fields["status"]=="closed"
          && (in_array("date",$this->updates) || in_array("closedate",$this->updates))) {

         // Invalid dates : no change
         // closedate must be > solvedate
         if ($this->fields["closedate"] < $this->fields["solvedate"]) {
            addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);

            if (($key=array_search('closedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['closedate']);
            }
         }

         // closedate must be > create date
         if ($this->fields["closedate"]<$this->fields["date"]) {
            addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);
            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('closedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['closedate']);
            }
         }
      }

/*      if (in_array("users_id",$this->updates)) {
         $user = new User;
         $user->getFromDB($this->input["users_id"]);
         if (!empty($user->fields["email"])) {
            $this->updates[] = "user_email";
            $this->fields["user_email"] = $user->fields["email"];
         }
      }*/

      if (($key=array_search('status',$this->updates)) !== false
          && $this->oldvalues['status'] == $this->fields['status']) {
         unset($this->updates[$key]);
         unset($this->oldvalues['status']);
      }

      $sla = new SLA();
      // Set begin waiting date if needed
      if (($key=array_search('status',$this->updates)) !== false
          && ($this->fields['status'] == 'waiting' || $this->fields['status'] == 'solved')) {
         $this->updates[]                    = "begin_waiting_date";
         $this->fields["begin_waiting_date"] = $_SESSION["glpi_currenttime"];

         if ($this->fields['slas_id']>0) {
            $sla->deleteLevelsToDo($this);
         }
      }

      // Manage come back to waiting state
      if ($key=array_search('status',$this->updates) !== false
          && ($this->oldvalues['status'] == 'waiting'
               // From solved to another state than closed
              || ($this->oldvalues['status'] == 'solved' && $this->fields['status'] != 'closed'))) {

         // Compute ticket waiting time use calendar if exists
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();
         $delay_time   = 0;


         // Compute ticket waiting time use calendar if exists
         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            $delay_time = $calendar->getActiveTimeBetween($this->fields['begin_waiting_date'],
                                                          $_SESSION["glpi_currenttime"]);
         } else { // Not calendar defined
            $delay_time = strtotime($_SESSION["glpi_currenttime"])
                           -strtotime($this->fields['begin_waiting_date']);
         }


         // SLA case : compute sla duration
         if ($this->fields['slas_id']>0) {
            if ($sla->getFromDB($this->fields['slas_id'])) {
               $delay_time_sla  = $sla->getActiveTimeBetween($this->fields['begin_waiting_date'],
                                                             $_SESSION["glpi_currenttime"]);
               $this->updates[] = "sla_waiting_duration";
               $this->fields["sla_waiting_duration"] += $delay_time_sla;
            }

            // Compute new due date
            $this->updates[]          = "due_date";
            $this->fields['due_date'] = $sla->computeDueDate($this->fields['date'],
                                                             $this->fields["sla_waiting_duration"]);
            // Add current level to do
            $sla->addLevelToDo($this);

         } else {
            // Using calendar
            if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
               if ($this->fields['due_date'] > 0) {
                  // compute new due date using calendar
                  $this->updates[]          = "due_date";
                  $this->fields['due_date'] = $calendar->computeEndDate($this->fields['due_date'],
                                                                        $delay_time);
               }

            } else { // Not calendar defined
               if ($this->fields['due_date'] > 0) {
                  // compute new due date : no calendar so add computed delay_time
                  $this->updates[]          = "due_date";
                  $this->fields['due_date'] = date('Y-m-d H:i:s',
                                                   $delay_time+strtotime($this->fields['due_date']));
               }
            }
         }

         $this->updates[]                          = "ticket_waiting_duration";
         $this->fields["ticket_waiting_duration"] += $delay_time;

         // Reset begin_waiting_date
         $this->updates[]                    = "begin_waiting_date";
         $this->fields["begin_waiting_date"] = 'NULL';
      }

      // solve_delay_stat : use delay between opendate and solvedate
      if (in_array("solvedate",$this->updates)) {
         $this->updates[]                  = "solve_delay_stat";
         $this->fields['solve_delay_stat'] = $this->computeSolveDelayStat();
      }
      // close_delay_stat : use delay between opendate and closedate
      if (in_array("closedate",$this->updates)) {
         $this->updates[]                  = "close_delay_stat";
         $this->fields['close_delay_stat'] = $this->computeCloseDelayStat();
      }

      // takeintoaccount :
      //     - update done by someone who have update right / see also updatedatemod used by ticketfollowup updates
      if ($this->canUpdateItem() && $this->fields['takeintoaccount_delay_stat']==0) {
         $this->updates[] = "takeintoaccount_delay_stat";
         $this->fields['takeintoaccount_delay_stat'] = $this->computeTakeIntoAccountDelayStat();
      }
      // Do not take into account date_mod if no update is done
      if ((count($this->updates)==1 && ($key=array_search('date_mod',$this->updates)) !== false)) {
         unset($this->updates[$key]);
      }
   }


   /// Compute take into account stat of the current ticket
   function computeTakeIntoAccountDelayStat() {

      if (isset($this->fields['id'])) {
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return $calendar->getActiveTimeBetween($this->fields['date'],
                                                   $_SESSION["glpi_currenttime"]);
         }
         // Not calendar defined
         return strtotime($_SESSION["glpi_currenttime"])-strtotime($this->fields['date']);
      }
      return 0;
   }


   /// Compute solve delay stat of the current ticket
   function computeSolveDelayStat() {

      if (isset($this->fields['id'])) {
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return $calendar->getActiveTimeBetween($this->fields['date'],
                                                   $this->fields['solvedate'])
                                                         -$this->fields["ticket_waiting_duration"];
         }
         // Not calendar defined
         return strtotime($this->fields['solvedate'])-strtotime($this->fields['date'])
                                                     -$this->fields["ticket_waiting_duration"];
      }
      return 0;
   }


   /// Compute close delay stat of the current ticket
   function computeCloseDelayStat() {

      if (isset($this->fields['id'])) {
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return $calendar->getActiveTimeBetween($this->fields['date'],$this->fields['closedate'])-$this->fields["ticket_waiting_duration"];
         }
         // Not calendar defined
         return strtotime($this->fields['closedate'])-strtotime($this->fields['date'])
                                                     -$this->fields["ticket_waiting_duration"];
      }
      return 0;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI, $LANG;

      $donotif = false;

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }

      if (count($this->updates)) {
         // New values for add followup in change
         $change_followup_content = "";
         if (isset($this->input['_doc_added']) && count($this->input['_doc_added'])>0) {
            foreach ($this->input['_doc_added'] as $name) {
               $change_followup_content .= $LANG['mailing'][26]." $name\n";
            }
         }
         // Update Ticket Tco
         if (in_array("actiontime",$this->updates)
             || in_array("cost_time",$this->updates)
             || in_array("cost_fixed",$this->updates)
             || in_array("cost_material",$this->updates)) {

            if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
               $item = new $this->fields["itemtype"]();
               if ($item->getFromDB($this->fields["items_id"])) {
                  $newinput = array();
                  $newinput['id']         = $this->fields["items_id"];
                  $newinput['ticket_tco'] = self::computeTco($item);
                  $item->update($newinput);
               }
            }
         }

         // Setting a solution type means the ticket is solved
         if ((in_array("ticketsolutiontypes_id",$this->updates)
               || in_array("solution",$this->updates))
               && ($this->fields["status"] == "solved"
                  || $this->fields["status"] == "closed")) { // auto close case
            Ticket_Ticket::manageLinkedTicketsOnSolved($this->fields['id']);
         }

         // Clean content to mail
         $this->fields["content"] = stripslashes($this->fields["content"]);
         $donotif = true;

      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif && $CFG_GLPI["use_mailing"]) {
         $mailtype = "update";

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && $this->input["status"]=="solved") {

            $mailtype = "solved";
         }

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && $this->input["status"]=="closed") {

            $mailtype = "closed";
         }

         // Read again ticket to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);

      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI, $LANG;

      // Check mandatory
      $mandatory_ok = true;

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         $_SESSION["helpdeskSaved"] = $input;

         if (!isset($input["urgency"])) {
            addMessageAfterRedirect($LANG['tracking'][4], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_content_mandatory"]
             && (!isset($input['content']) || empty($input['content']))) {

            addMessageAfterRedirect($LANG['tracking'][8], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_title_mandatory"]
             && (!isset($input['name']) || empty($input['name']))) {

            addMessageAfterRedirect($LANG['help'][40], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_category_mandatory"]
             && (!isset($input['ticketcategories_id']) || empty($input['ticketcategories_id']))) {

            addMessageAfterRedirect($LANG['help'][41], false, ERROR);
            $mandatory_ok = false;
         }

//          if (isset($input['use_email_notification']) && $input['use_email_notification']
//              && (!isset($input['user_email']) || empty($input['user_email']))) {
//
//             addMessageAfterRedirect($LANG['help'][16], false, ERROR);
//             $mandatory_ok = false;
//          }

         if (!$mandatory_ok) {
            return false;
         }
      }

      if (!isset($input["urgency"])
          || !($CFG_GLPI['urgency_mask']&(1<<$input["urgency"]))) {
         $input["urgency"] = 3;
      }
      if (!isset($input["impact"])
          || !($CFG_GLPI['impact_mask']&(1<<$input["impact"]))) {
         $input["impact"] = 3;
      }
      if (!isset($input["priority"])) {
         $input["priority"] = $this->computePriority($input["urgency"], $input["impact"]);
      }

      unset($_SESSION["helpdeskSaved"]);

      // No Auto set Import for external source
      if (!isset($input['_auto_import'])) {
         if (!isset($input["_users_id_requester"])) {
            if ($uid = getLoginUserID()) {
               $input["_users_id_requester"] = $uid;
            }
         }
      }

      // set last updater
      if ($lastupdater=getLoginUserID(true)) {
         $input['users_id_lastupdater'] = $lastupdater;
      }

      // No Auto set Import for external source
      if (($uid=getLoginUserID()) && !isset($input['_auto_import'])) {
         $input["users_id_recipient"] = $uid;
      } else if (isset ($input["_users_id_requester"]) && $input["_users_id_requester"]) {
         $input["users_id_recipient"] = $input["_users_id_requester"];
      }

      if (!isset($input["requesttypes_id"])) {
         $input["requesttypes_id"] = RequestType::getDefault('helpdesk');
      }
      if (!isset($input["status"])) {
         $input["status"] = "new";
      }
      if (!isset($input['global_validation'])) {
         $input['global_validation'] = 'none';
      }
      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      if (!isset($input["_users_id_assign"])) {
         $input["_users_id_assign"] = 0;
      }
      if (!isset($input["_groups_id_assign"])) {
         $input["_groups_id_assign"] = 0;
      }
      // Set default dropdown
      $dropdown_fields = array('entities_id', 'items_id', 'suppliers_id_assign',
                               'ticketcategories_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }
      if (!isset($input['itemtype']) || !($input['items_id']>0)) {
         $input['itemtype'] = '';
      }

      $item = NULL;
      if ($input["items_id"]>0 && !empty($input["itemtype"])) {
         if (class_exists($input["itemtype"])) {
            $item = new $input["itemtype"]();
            if (!$item->getFromDB($input["items_id"])) {
               $item = NULL;
            }
         }
      }

      // Auto group define from item
//       if ($item != NULL) {
//          if ($item->isField('groups_id')
//              && (!isset($input["_groups_id_requester"]) || $input["_groups_id_requester"]==0)) {
//             $input["_groups_id_requester"] = $item->getField('groups_id');
//          }
//       }

      // Manage auto assign
      $entitydata = new EntityData;
      $auto_assign_mode = $CFG_GLPI['auto_assign_mode'];
      if ($entitydata->getFromDB($input['entities_id'])) {
         $auto_assign_mode = $entitydata->getField('auto_assign_mode');
         // Set global config value
         if ($auto_assign_mode == -1) {
            $auto_assign_mode = $CFG_GLPI['auto_assign_mode'];
         }
      }
      switch ($auto_assign_mode) {
         case NO_AUTO_ASSIGN :
            break;

         case AUTO_ASSIGN_HARDWARE_CATEGORY :
            // Auto assign tech from item
            if ($input["_users_id_assign"]==0 && $item!=NULL) {
               if ($item->isField('users_id_tech')) {
                  $input["_users_id_assign"] = $item->getField('users_id_tech');
                  if ($input["_users_id_assign"]>0) {
                     $input["status"] = "assign";
                  }
               }
            }
            // Auto assign tech/group from Category
            if ($input['ticketcategories_id']>0
                && (!$input['_users_id_assign'] || !$input['_groups_id_assign'])) {

               $cat = new TicketCategory();
               $cat->getFromDB($input['ticketcategories_id']);
               if (!$input['_users_id_assign'] && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if (!$input['_groups_id_assign'] && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            break;

         case AUTO_ASSIGN_CATEGORY_HARDWARE :
            // Auto assign tech/group from Category
            if ($input['ticketcategories_id']>0
                && (!$input['_users_id_assign'] || !$input['_groups_id_assign'])) {

               $cat = new TicketCategory();
               $cat->getFromDB($input['ticketcategories_id']);
               if (!$input['_users_id_assign'] && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if (!$input['_groups_id_assign'] && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            // Auto assign tech from item
            if ($input["_users_id_assign"]==0 && $item!=NULL) {
               if ($item->isField('users_id_tech')) {
                  $input["_users_id_assign"] = $item->getField('users_id_tech');
                  if ($input["_users_id_assign"]>0) {
                     $input["status"] = "assign";
                  }
               }
            }
            break;
      }

      // Process Business Rules
      $rules = new RuleTicketCollection($input['entities_id']);

      // Set unset variables with are needed
      $user = new User();
      if (isset ($input["_users_id_requester"])
          && $user->getFromDB($input["_users_id_requester"])) {
         $input['users_locations'] = $user->fields['locations_id'];
      }

      $input = $rules->processAllRules($input, $input, array('recursive' => true));

//       if (isset($input["use_email_notification"])
//           && $input["use_email_notification"]
//           && empty($input["user_email"])) {
//
//          if ($user->getFromDB($input["users_id"])) {
//             $input["user_email"] = $user->fields["email"];
//          }
//       }

      if (((isset($input["_users_id_assign"]) && $input["_users_id_assign"]>0)
           || (isset($input["_groups_id_assign"]) && $input["_groups_id_assign"]>0)
           || (isset($input["suppliers_id_assign"]) && $input["suppliers_id_assign"]>0))
          && $input["status"]=="new") {

         $input["status"] = "assign";
      }

      if (isset($input["hour"]) && isset($input["minute"])) {
         $input["actiontime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
         $input["_hour"]      = $input["hour"];
         $input["_minute"]    = $input["minute"];
         unset($input["hour"]);
         unset($input["minute"]);
      }

      if (isset($input["status"]) && $input["status"]=="solved") {
         if (isset($input["date"])) {
            $input["solvedate"] = $input["date"];
         } else {
            $input["solvedate"] = $_SESSION["glpi_currenttime"];
         }
      }

      if (isset($input["status"]) && $input["status"]=="closed") {
         if (isset($input["date"])) {
            $input["closedate"] = $input["date"];
         } else {
            $input["closedate"] = $_SESSION["glpi_currenttime"];
         }
         $input['solvedate']=$input["closedate"];
      }

      // Set begin waiting time if status is waiting
      if (isset($input["status"]) && $input["status"]=="waiting") {
         $input['begin_waiting_date'] = $input['date'];
      }

      // No name set name
      if (empty($input["name"])) {
         $input["name"] = preg_replace('/\r\n/',' ',$input['content']);
         $input["name"] = preg_replace('/\n/',' ',$input['name']);
         $input["name"] = utf8_substr($input['name'],0,70);
      }

      //// Manage SLA assignment
      // due date defined : no SLA
      if (isset($input["due_date"]) && $input['due_date'] != 'NULL') {
         // Valid due date
         if ($input['due_date']>$input['date']) {
            if (isset($input["slas_id"])) {
               unset($input["slas_id"]);
            }
         } else {
            // Unset due date
            unset($input["due_date"]);
         }
      }

      if (isset($input["slas_id"]) && $input["slas_id"]>0) {
         $sla = new SLA();
         if ($sla->getFromDB($input["slas_id"])) {
            // Get first SLA Level
            $input["slalevels_id"] = SlaLevel::getFirstSlaLevel($input["slas_id"]);
            // Compute due_date
            $input['due_date']             = $sla->computeDueDate($input['date']);
            $input['sla_waiting_duration'] = 0;

         } else {
            $input["slalevels_id"]         = 0;
            $input["slas_id"]              = 0;
            $input['sla_waiting_duration'] = 0;
         }
      }

      // auto set type if not set
      if (!isset($input["type"])) {
         $input['type'] = EntityData::getUsedConfig('tickettype', $input['entities_id']);
      }

      return $input;
   }


   function post_addItem() {
      global $LANG, $CFG_GLPI;

      // Add document if needed
      $this->addFiles($this->fields['id']);

      if (isset($this->input["_followup"])
          && is_array($this->input["_followup"])
          && strlen($this->input["_followup"]['content']) > 0) {

         $fup  = new TicketFollowup();
         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id']);

         if (isset($this->input["_followup"]['content'])
             && strlen($this->input["_followup"]['content']) > 0) {
            $toadd["content"] = $this->input["_followup"]['content'];
         }

         if (isset($this->input["_followup"]['is_private'])) {
            $toadd["is_private"] = $this->input["_followup"]['is_private'];
         }
         $toadd['_no_notif'] = true;

         $fup->add($toadd);
      }

      if (isset($this->input["plan"])
          || (isset($this->input["_hour"])
              && isset($this->input["_minute"])
              && isset($this->input["realtime"])
              && $this->input["realtime"]>0)) {

         $task = new TicketTask();
         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id']);
         if (isset($this->input["_hour"])) {
            $toadd["hour"] = $this->input["_hour"];
         }
         if (isset($this->input["_minute"])) {
            $toadd["minute"] = $this->input["_minute"];
         }
         if (isset($this->input["plan"])) {
            $toadd["plan"] = $this->input["plan"];
         }
         $toadd['_no_notif'] = true;

         $task->add($toadd);
      }

      $ticket_ticket = new Ticket_Ticket();

      // From interface
      if (isset($this->input['_link'])) {
         $this->input['_link']['tickets_id_1'] = $this->fields['id'];
         if ($ticket_ticket->can(-1, 'w', $this->input['_link'])) {
            $ticket_ticket->add($this->input['_link']);
         }
      }

      // From mailcollector : do not check rights
      if (isset($this->input["_linkedto"])) {
         $input2['tickets_id_1'] = $this->fields['id'];
         $input2['tickets_id_2'] = $this->input["_linkedto"];
         $input2['link']         = Ticket_Ticket::LINK_TO;
         $ticket_ticket->add($input2);
      }

      // Manage SLA Level : add actions
      if (isset($this->input["slas_id"])
          && $this->input["slas_id"]>0
          && isset($this->input["slalevels_id"])
          && $this->input["slalevels_id"]>0) {

         $sla = new SLA();
         if ($sla->getFromDB($this->input["slas_id"])) {
            // Add first level in working table
            if ($this->input["slalevels_id"]>0) {
               $sla->addLevelToDo($this);
            }
         }
      }


      // Add user groups linked to tickets
      $ticket_user  = new Ticket_User;
      $group_ticket = new Group_Ticket;

      if (isset($this->input["_users_id_requester"])
          && ($this->input["_users_id_requester"]>0
              || (isset($this->input["_users_id_requester_notif"]['alternative_email'])
                  && !empty($this->input["_users_id_requester_notif"]['alternative_email'])))) {
         $input2 = array('tickets_id' => $this->fields['id'],
                         'users_id'   => $this->input["_users_id_requester"],
                         'type'       => self::REQUESTER);
         if (isset($this->input["_users_id_requester_notif"])) {
            foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
               $input2[$key] = $val;
            }
         }
         $ticket_user->add($input2);
      }
      if (isset($this->input["_users_id_observer"])
          && ($this->input["_users_id_observer"]>0
              || (isset($this->input["_users_id_observer_notif"]['alternative_email'])
                  && !empty($this->input["_users_id_observer_notif"]['alternative_email'])))) {
         $input2 = array('tickets_id' => $this->fields['id'],
                         'users_id'   => $this->input["_users_id_observer"],
                         'type'       => self::OBSERVER);
         if (isset($this->input["_users_id_observer_notif"])) {
            foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
               $input2[$key] = $val;
            }
         }
         $ticket_user->add($input2);
      }
      if (isset($this->input["_users_id_assign"]) && $this->input["_users_id_assign"]>0) {
         $input2 = array('tickets_id' => $this->fields['id'],
                         'users_id'   => $this->input["_users_id_assign"],
                         'type'       => self::ASSIGN);
         if (isset($this->input["_users_id_assign_notif"])) {
            foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
               $input2[$key] = $val;
            }
         }
         $ticket_user->add($input2);
      }

      if (isset($this->input["_groups_id_requester"]) && $this->input["_groups_id_requester"]>0) {
         $group_ticket->add(array('tickets_id' => $this->fields['id'],
                                  'groups_id'  => $this->input["_groups_id_requester"],
                                  'type'       => self::REQUESTER));
      }
      if (isset($this->input["_groups_id_assign"]) && $this->input["_groups_id_assign"]>0) {
         $group_ticket->add(array('tickets_id' => $this->fields['id'],
                                  'groups_id'  => $this->input["_groups_id_assign"],
                                  'type'       => self::ASSIGN));
      }
      if (isset($this->input["_groups_id_observer"]) && $this->input["_groups_id_observer"]>0) {
         $group_ticket->add(array('tickets_id' => $this->fields['id'],
                                  'groups_id'  => $this->input["_groups_id_observer"],
                                  'type'       => self::OBSERVER));
      }


      // Additional actors : using default notification parameters
      // Observers : for mailcollector
      if (isset($this->input["_additional_observers"])
          && is_array($this->input["_additional_observers"])
          && count($this->input["_additional_observers"])) {

         $input2 = array('tickets_id' => $this->fields['id'],
                         'type'       => self::OBSERVER);

         foreach ($this->input["_additional_observers"] as $tmp) {
            if (isset($tmp['users_id'])) {
               foreach ($tmp as $key => $val) {
                  $input2[$key] = $val;
               }

               $ticket_user->add($input2);
            }
         }
      }

      if (isset($this->input["_additional_assigns"])
          && is_array($this->input["_additional_assigns"])
          && count($this->input["_additional_assigns"])) {

         $input2 = array('tickets_id' => $this->fields['id'],
                         'type'       => self::ASSIGN);

         foreach ($this->input["_additional_assigns"] as $tmp) {
            if (isset($tmp['users_id'])) {
               foreach ($tmp as $key => $val) {
                  $input2[$key] = $val;
               }

               $ticket_user->add($input2);
            }
         }
      }

      if (isset($this->input["_additional_requesters"])
          && is_array($this->input["_additional_requesters"])
          && count($this->input["_additional_requesters"])) {

         $input2 = array('tickets_id' => $this->fields['id'],
                         'type'       => self::REQUESTER);

         foreach ($this->input["_additional_requesters"] as $uid) {
            $input2['users_id'] = $uid;
            $ticket_user->add($input2);
         }
      }


      //Action for send_validation rule
      if (isset($this->input["_add_validation"]) && $this->input["_add_validation"]>0) {

         $validation = new Ticketvalidation();
         $values['tickets_id']        = $this->fields['id'];
         $values['users_id_validate'] = $this->input["_add_validation"];

         if ($validation->can(-1, 'w', $values)) {
            $validation->add($values);

            Event::log($this->fields['id'], "ticket", 4, "tracking",
                       $_SESSION["glpiname"]."  ".$LANG['log'][21]);
         }
      }

      // Processing Email
      if ($CFG_GLPI["use_mailing"]) {
         // Clean reload of the ticket
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         NotificationEvent::raiseEvent($type, $this);
      }

      if (isset($_SESSION['glpiis_ids_visible']) && !$_SESSION['glpiis_ids_visible']) {
         addMessageAfterRedirect($LANG['help'][18]." (".$LANG['job'][38]."&nbsp;".
                                 "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
                                 $this->fields['id']."'>".$this->fields['id']."</a>)");
      }

   }


   // SPECIFIC FUNCTIONS
   /**
    * Number of followups of the ticket
    *
    * @param $with_private boolean : true : all followups / false : only public ones
    *
    * @return followup count
   **/
   function numberOfFollowups($with_private=1) {
      global $DB;

      $RESTRICT = "";
      if ($with_private!=1) {
         $RESTRICT = " AND `is_private` = '0'";
      }

      // Set number of followups
      $query = "SELECT count(*)
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Number of tasks of the ticket
    *
    * @param $with_private boolean : true : all ticket / false : only public ones
    *
    * @return followup count
   **/
   function numberOfTasks($with_private=1) {
      global $DB;

      $RESTRICT = "";
      if ($with_private!=1) {
         $RESTRICT = " AND `is_private` = '0'";
      }
      // Set number of followups
      $query = "SELECT count(*)
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Update actiontime of the ticket based on actiontime of the followups and tasks
    *
    * @param $ID ID of the ticket
    *
    * @return boolean : success
   **/
   function updateActionTime($ID) {
      global $DB;

      $tot = 0;

      $query = "SELECT SUM(`actiontime`)
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '$ID'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result,0,0);
         if (!is_null($sum)) {
            $tot += $sum;
         }
      }
      $query2 = "UPDATE `".$this->getTable()."`
                 SET `actiontime` = '$tot'
                 WHERE `id` = '$ID'";

      return $DB->query($query2);
   }


   /**
    * Update date mod of the ticket
    *
    * @param $ID ID of the ticket
    * @param $no_stat_computation boolean do not cumpute take into account stat
   **/
   function updateDateMod($ID, $no_stat_computation=false) {
      global $DB;

      if ($this->getFromDB($ID)) {
         if (!$no_stat_computation
             && (haveRight("global_add_tasks", "1")
                 || haveRight("global_a                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                