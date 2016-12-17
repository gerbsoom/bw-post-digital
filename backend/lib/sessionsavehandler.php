<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

/**
 * SessionSaveHandler which internally uses a Rediska cache.
 *
 * To avoid problems with session data being en- and decoded in different environments,
 * where clashes might occur, all processed data is wrapped with a JSON-transformation.
 */
class SessionSaveHandler
{
    /** @var Monolog\Logger */
    private $log;
    /** @var Rediska */
    private $redisCache;
    private $sessionKeyBase;

    private $sessionName;
    private $sessionLifetime;

    /**
     * Constructs the RedisSessionSaveHandler with a given Rediska cache and key base dir.
     *
     * @param Rediska $_redisCache The Rediska cache where the session data gets stored.
     * @param string $_sessionKeyBase The key base dir where the session data is stored.
     */
    public function __construct(Rediska $_redisCache, $_sessionKeyBase="sessionKeys/")
    {
        $this->redisCache = $_redisCache;
        $this->sessionKeyBase = $_sessionKeyBase;
        $this->log = LoggerRegistry::getLogger($this);
    }

    /**
     * Closes the session.
     *
     * @return bool Nothing to fail here.
     */
    public function close()
    {
        $this->log->debug("Close the session");
        $this->log->debug("_____________________________________________________________________________________________________");
        return true;
    }

    /**
     * Destroys the session, deleting all session data for the provided ID.
     *
     * @param string $_id The ID for which the session data gets deleted.
     * @return bool True means that the session data could be deleted.
     */
    public function destroy($_id)
    {
        $this->log->debug("Destroy the session with ID: $_id");
        $this->redisCache->delete($this->sessionKeyBase.$_id);
        return true;
    }

    /**
     * Cleanup is handled inside Rediska by set expire date.
     *
     * @param $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $this->log->debug("Discard gc()");
        return true;
    }

    /**
     * Opens a session and defines name and lifetime of the data.
     *
     * @param $savePath
     * @param $_name
     * @return bool
     */
    public function open($savePath, $_name)
    {
        $this->log->debug("_____________________________________________________________________________________________________");
        $this->log->debug("Open a session for (session) name: $_name");
        $this->sessionName = $_name;
        $this->sessionLifetime = ini_get("session.gc_maxlifetime");
        $this->log->debug("Setting lifetime to ".$this->sessionLifetime);
        return true;
    }

    /**
     * Reads the session data of a given ID.
     *
     * @param string $_id The ID for which the session data is returned.
     * @return string The session data or an empty string for a new session.
     */
    public function read($_id)
    {
        $this->log->debug("Read session data for ID $_id");
        $_SESSION = json_decode($this->redisCache->get($this->sessionKeyBase.$_id), true);
        if (isset($_SESSION) && !empty($_SESSION) && $_SESSION != null)
        {// use the stored session from cache
            $sessionData = session_encode();
            $this->log->debug("--> Existing session '$sessionData'");
            return $sessionData;
        }
        $this->log->debug("--> NEW session");
        // new session
        return "";
    }

    /**
     * Writes the session data by storing it in the underlying Rediska cache.
     *
     * The provided \c $_data is ignored because it already is serialized by PHP and might clash.
     * We store instead the complete session data, contained in $_SESSION as encoded JSON-string.
     *
     * @param string $_id The ID for which the session data gets stored.
     * @param string $_data Ignored and serialized by manually JSON-encoding $_SESSION.
     * @return bool True means that storing the session data finishes successfully.
     */
    public function write($_id, $_data)
    {
        $this->log->debug("Writing session data ".json_encode($_SESSION)." to rediska");
        $this->redisCache->setAndExpire($this->sessionKeyBase.$_id,
                                        json_encode($_SESSION),
                                        $this->sessionLifetime);
        return true;
    }
}
