<?php

namespace ratingallocate\ssh;

abstract class authentication {
    
    public abstract function authenticate($connection);

};

class none_authentication extends authentication {

    private $username = '';
    
    public function __construct($username) {
        $this->username = $username;
    }
    
    public function set_username($username) {
        $this->username = $username;
    }

    public function get_username() {
        return $this->username;
    }

    public function authenticate($connection) {
        return ssh2_auth_none($connection, $this->username);
    }

};

class password_authentication extends none_authentication {

    private $password;
    
    public function __construct($username, $password) {
        parent::__construct($username);
        $this->password = $password;
    }

    public function set_password($password) {
        $this->password = $password;
    }

    public function get_password() {
        return $this->password;
    }
    
    public function authenticate($connection) {
        return ssh2_auth_password($connection, $this->get_username(), $this->get_password());        
    }

};

class connection {

    private $address = '';
    private $fingerprint = false;
    private $authentication = null;

    private $handle = null;
    
    /**
     * Creates a new SSH connection
     *
     * @param $address Address of the ssh server
     * @param $fingerprint Fingerprint of the ssh server (null for none)
     * @param $authentication Authentication method
     *
     * @throws Exception If the connection to the ssh server could not be established
     * @throws Exception If authentication failed
     */
    public function __construct($address, $fingerprint, $authentication) {
        $this->address = $address;
        $this->fingerprint = $fingerprint;
        $this->authentication = $authentication;

        $this->handle = \ssh2_connect($this->address);

        if($this->fingerprint && ssh2_fingerprint($this->handle) != $this->fingerprint)
            throw new \Exception("Fingerprints do not match!");
        
        if(!$this->handle)
            throw new \Exception("Could not connect to ssh server with address {$this->address}!");

        if(!$this->authentication->authenticate($this->handle))
            throw new \Exception('Authentication failed!');
    }

    /**
     * Returns the address of the ssh server
     *
     * @return SSH server address
     */
    public function get_address() {
        return $this->address;
    }
    
    /**
     * Returns the fingerprint of the ssh server
     *
     * @return SSH server fingerprint
     */
    public function get_fingerprint() {
        return $this->fingerprint;
    }
    
    /**
     * Returns the authentication method of the ssh server
     *
     * @return Authentication method
     */
    public function get_authentication() {
        return $this->authentication;
    }
    
    /**
     * Returns the connection handle of the ssh server
     *
     * @return SSH server connection handle
     */
    public function get_handle() {
        return $this->handle;
    }

    /**
     * Executes a command
     *
     * @param $command Command which gets executed
     *
     * @throws Exception If the command could not be executed
     *
     * @return Stream handle
     */
    public function execute($command) {
        $stream = \ssh2_exec($this->handle, $command);
        
        if(!$stream)
            throw new \exception("Could not execute the command {$this->command}!");
        
        stream_set_blocking($stream, true);

        return $stream;        
    }

    /**
     * Sends a file to a remote server
     *
     * @param $local_file Name of the local file
     * @param $remote_file Name of the remote file
     *
     * @throws Exception If file was not transmitted successfully     
     */
    public function send_file($local_file, $remote_file) {
        if(!ssh2_scp_send($this->handle, $local_file, $remote_file))
            throw \exception('Error sending file!');
    }

    /**
     * Receives a file from a remote server
     *
     * @param $remote_file Name of the remote file
     * @param $local_file Name of the local file
     *
     * @throws Exception If file was not transmitted successfully
     */
    public function receive_file($remote_file, $local_file) {
        if(!ssh2_scp_recv($this->handle, $remote_file, $local_file))
            throw \exception('Error receiving file!');
    }

};