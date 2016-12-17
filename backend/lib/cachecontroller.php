<?php
/**
 * This file is part of yaBotGame.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2014
 * @author Markus Riegert <desmodul@drow-land.de>
 */

require_once(__DIR__ . "/../../../boot.php");

/**
 * Provides an API to store game-related data persistent into a Redis server.
 */
class CacheController
{
    /** @var  Monolog\Logger */
    private $log;
    /** @var Rediska */
    private $rediska;
    private $rediskaBase;
    /** @var CacheUser[]  */
    private $cacheUsers = array();
    /** @var CacheGame[]  */
    private $cacheGames = array();
    /** @var UpdateChannel  */
    private $updateChannel;

    /**
     * Constructs the cache API with a given cache base key.
     *
     * @param string $_rediskaBase Defines the cache base key where the game data gets stored.
     */
    public function __construct($_rediskaBase="botgame/")
    {
        $this->log = LoggerRegistry::getLogger($this);
        $this->rediskaBase = $_rediskaBase;
        $this->rediska = Rediska_Manager::get();
        $this->updateChannel = new UpdateChannel();
        $this->gameSessionKeys = new Rediska_Key_Hash($_rediskaBase."gameSessionKeys");
        $this->runningGames = new Rediska_Key_List($this->rediskaBase."runningGames");
        $this->registeredUsers = new Rediska_Key_List($this->rediskaBase."registeredUsers");
        $this->loggedInUsers = new Rediska_Key_List($this->rediskaBase."loggedInUsers");

        foreach ($this->runningGames as $runningGame)
        {
            $this->cacheGames[$runningGame] = new CacheGame($this->updateChannel, $this->rediska, $_rediskaBase, $runningGame);
        }
        foreach ($this->registeredUsers as $registeredUser)
        {
            $this->cacheUsers[$registeredUser] = new CacheUser($this->updateChannel, $this->rediska, $_rediskaBase, $registeredUser);
        }
    }

    /**
     * Checks if the provided user name is a registered user.
     *
     * @param string $_userName The user name for which the check is done.
     * @return bool True means that the provided user name is a registered user.
     */
    public function registeredUserExists($_userName)
    {
        return isset($this->cacheUsers[$_userName]);
    }

    /**
     * Creates a new registered user account.
     *
     * toDo: Replace with a stronger hash or better zero-knowledge algorithm.
     *
     * @param string $_userName The name of the user for which the account is created.
     * @param string $_passwordHash The password hash of the user for which the account is created.
     */
    public function createUserAccount($_userName, $_passwordHash)
    {
        $this->log->debug("Creating User Account for $_userName");
        $this->cacheUsers[$_userName] = new CacheUser($this->updateChannel, $this->rediska, $this->rediskaBase, $_userName);
        $this->cacheUsers[$_userName]->createAccount($_passwordHash);
        $this->registeredUsers[] = $_userName;
    }

    /**
     * Completely deletes a user account from the cache.
     *
     * @param $_userName string The name of the user account that gets deleted.
     */
    public function deleteUserAccount($_userName)
    {
        $this->log->debug("Delete user account $_userName");
        // delete user from all lists: running, created and joined games or alter them to a new leader
        // delete all registered bots from the user
        if (isset($this->cacheUsers[$_userName]))
        {
            $this->cacheUsers[$_userName]->deleteAccount();
        }
        unset ($this->cacheUsers[$_userName]);
    }

    /**
     * Verifies that the given password hash fits with the stored md5 sum.
     *
     * @param string $_userName The user name for which the check is done.
     * @param string $_passwordHash The hash of the user that gets verified.
     * @return bool True means that the given password hash fits with the stored md5 sum.
     */
    public function verifyPasswordHash($_userName, $_passwordHash)
    {
        $this->log->debug("Verifying password for $_userName");
        if (isset($this->cacheUsers[$_userName]))
        {
            return $this->cacheUsers[$_userName]->verifyPassword($_passwordHash);
        }
        return false;
    }

    /**
     * Updates the password hash with a new version.
     *
     * @param string $_userName The user name for which the password hash is updated.
     * @param string $_passwordHash The hash of the user that replaces the old one.
     */
    public function updatePasswordHash($_userName, $_passwordHash)
    {
        $this->log->debug("Updating password hash for user $_userName");
        if (isset($this->cacheUsers[$_userName]))
        {
            $this->cacheUsers[$_userName]->updatePassword($_passwordHash);
        }
    }

    /**
     * Add the given user name to the list of logged in users.
     *
     * @param string $_userName The user name that gets added to the list of logged in users.
     */
    public function addLoggedInUser($_userName)
    {
        $this->log->debug("Add logged in user $_userName");
        $this->loggedInUsers[] = $_userName;
    }

    /**
     * Returns a list of all known users to cyclically do checks on them.
     *
     * @return array The list of all known users.
     */
    public function getRegisteredUsers()
    {
        $this->log->debug("Returning registered user list");
        return $this->registeredUsers->toArray();
    }

    /**
     * Returns a list of all users that are currently logged in.
     *
     * @return array The list of all users that are currently logged in.
     */
    public function getLoggedInUsers()
    {
        $this->log->debug("Returning logged in user list");
        return $this->loggedInUsers->toArray();
    }

    /**
     * Checks if the given user name is currently logged in.
     *
     * @param string $_userName The user name for which the check is done.
     * @return bool True means that the given user name is currently logged in.
     */
    public function isUserLoggedIn($_userName)
    {
        $this->log->debug("Checking if user $_userName is logged in");
        return in_array($_userName, $this->loggedInUsers->getValues());
    }

    /**
     * Logs out the given user name removing it from the list of logged in users.
     *
     * @param string $_userName The user that gets logged off.
     */
    public function logOutUser($_userName)
    {
        $this->log->debug("Logging out user $_userName");
        $this->loggedInUsers->remove($_userName);
    }

    /**
     * Updates the last activity (logging the action) of a given user to prevent auto-logoff.
     *
     * @param string $_loginName The name of the user for which the last activity gets updated.
     * @param string $_backendAction The backend activity (ex.: game::listGames) which gets logged.
     */
    public function updateLastActivity($_loginName, $_backendAction)
    {
        $this->log->debug("updateLastActivity() for user $_loginName with $_backendAction");
        if (isset($this->cacheUsers[$_loginName]))
        {
            $this->cacheUsers[$_loginName]->updateLastActivity($_backendAction);
        }
    }

    /**
     * Checks if the session of the given user is time out due to too long inactivity.
     *
     * @param string $_loginName The name of the user for which the check is done.
     * @param int $_maxSessionTimeout The maximum amount of time in seconds before auto-logoff.
     * @return bool True means that the user was too long inactive and the session is timed out.
     */
    public function sessionTimedOut($_loginName, $_maxSessionTimeout = 1500)
    {
        $this->log->debug("sessionTimedOut() for $_loginName");
        if (isset($this->cacheUsers[$_loginName]))
        {
            if ($this->cacheUsers[$_loginName]->sessionTimedOut($_maxSessionTimeout))
            {
                $this->logOutUser($_loginName);
                return true;
            }
            else return false;
        }
        return null;
    }

    /**
     * Creates a new game with the provided parameters
     *
     * @param string $_loginName The name of the user that creates the game.
     * @param string $_gameName The name of the game that gets created.
     * @param int $_width The width of the game that gets created.
     * @param int $_height The height of the game that gets created.
     * @param int $_maxPlayer Defines the player maximum of the game that gets created.
     */
    public function createNewGame($_loginName, $_gameName, $_width, $_height, $_maxPlayer=4)
    {
        $this->log->debug("Creating ($_width x $_height) game $_gameName for user $_loginName");

        $this->cacheGames[$_gameName] = new CacheGame($this->updateChannel, $this->rediska, $this->rediskaBase, $_gameName);
        $this->cacheGames[$_gameName]->createGame($_loginName, $_width, $_height, $_maxPlayer);
        $this->runningGames[] = $_gameName;
    }

    /**
     * Returns the width of the given gameName.
     *
     * @param string $_gameName The name of the game for which the width is retrieved.
     * @return int The width of the game.
     */
    public function getGameWidth($_gameName)
    {
        $this->log->debug("getGameWidth()");
        if (isset($this->cacheGames[$_gameName]))
        {
            return $this->cacheGames[$_gameName]->getWidth();
        }
        else return null;
    }

    /**
     * Returns the height of the given gameName.
     *
     * @param string $_gameName The name of the game for which the height is retrieved.
     * @return int The height of the game.
     */
    public function getGameHeight($_gameName)
    {
        $this->log->debug("getGameHeight()");
        if (isset($this->cacheGames[$_gameName]))
        {
            return $this->cacheGames[$_gameName]->getHeight();
        }
        else return null;
    }

    /**
     * Writes the provided FieldData into the game cache.
     *
     * @param FieldData $_fieldData The FieldData that gets inserted into the game cache.
     * @param string $_gameName The name of the game where the FieldData gets inserted.
     * @param int $_posX The X-values of the inserted FieldData.
     * @param int $_posY The Y-values of the inserted FieldData.
     */
    public function setFieldData(FieldData $_fieldData, $_gameName, $_posX, $_posY)
    {
        $this->log->debug("setFieldData (".$_fieldData->type.") for game $_gameName at ($_posX x $_posY)");
        if (isset($this->cacheGames[$_gameName]))
        {
            $this->cacheGames[$_gameName]->setFieldData($_fieldData, $_posX, $_posY);
        }
    }

    /**
     * Retrieves the current FieldData for the requested coordinate.
     *
     * @param string $_gameName The name of the game from where the FieldData is retrieved.
     * @param int $_posX The X-values of the requested FieldData.
     * @param int $_posY The Y-values of the requested FieldData.
     * @return FieldData The current FieldData at the requested coordinate.
     */
    public function getFieldData($_gameName, $_posX, $_posY)
    {
        $this->log->debug("setFieldData for game $_gameName at ($_posX x $_posY)");
        if (isset($this->cacheGames[$_gameName]))
        {
            return $this->cacheGames[$_gameName]->getFieldData($_posX, $_posY);
        }
        return null;
    }

    /**
     * Checks if the provided game name already exists as running game.
     *
     * @param string $_gameName The name of the game for which the check is done.
     * @return bool True means that the
     */
    public function checkIfGameExists($_gameName)
    {
        $this->log->debug("Check if game $_gameName exists");
        return in_array($_gameName, $this->runningGames->getValues());
    }

}
