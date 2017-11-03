<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_ratingallocate\local\ssh;

class connection {

    private $address = '';
    private $fingerprint = false;
    private $authentication = null;

    private $handle = null;

    /**
     * Creates a new SSH connection
     *
     * @param $address Address of the ssh server
     * @param $authentication Authentication method
     * @param $fingerprint Fingerprint of the ssh server (null for none)
     *
     * @throws exception If the connection to the ssh server could not be established
     * @throws exception If authentication failed
     */
    public function __construct($address, $authentication, $fingerprint = null) {
        $this->address = $address;
        $this->authentication = $authentication;
        $this->fingerprint = $fingerprint;

        $this->handle = \ssh2_connect($this->address);

        if($this->fingerprint && ssh2_fingerprint($this->handle) != $this->fingerprint)
            throw new \exception("Fingerprints do not match!");

        if(!$this->handle)
            throw new \exception("Could not connect to ssh server with address {$this->address}!");

        if(!$this->authentication->authenticate($this->handle))
            throw new \exception('Authentication failed!');
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
     * @throws exception If the command could not be executed
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
     * @param $local_path Local path
     * @param $remote_path Remote path
     *
     * @throws exception If file was not transmitted successfully
     */
    public function send_file($local_path, $remote_path) {
        if(!ssh2_scp_send($this->handle, $local_path, $remote_path))
            throw new \exception('Error sending file!');
    }

    /**
     * Receives a file from a remote server
     *
     * @param $remote_path Remote path
     * @param $local_path Local path
     *
     * @throws exception If file was not transmitted successfully
     */
    public function receive_file($remote_path, $local_path) {
        if(!ssh2_scp_recv($this->handle, $remote_path, $local_path))
            throw new \exception('Error receiving file!');
    }

}