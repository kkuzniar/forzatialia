<?php

// DO NOTE CALL THIS CLASS DIRECTLY. CALL VIA: pb_backupbuddy_destination in bootstrap.php.

class pb_backupbuddy_destination_stash { // Change class name end to match destination name.
	
	const ITXAPI_KEY = 'ixho7dk0p244n0ob';
	const ITXAPI_URL = 'http://api.ithemes.com';
	
	public static $destination_info = array(
		'name'			=>		'BackupBuddy Stash',
		'description'	=>		'<b>The easiest of all destinations</b>; just enter your iThemes login and Stash away! Store your backups in the BackupBuddy cloud safely with high redundancy and encrypted storage.  BackupBuddy Stash even supports multipart uploads so large files can be transferred in smaller pieces if needed rather than all at once. All BackupBuddy customers with an active BackupBuddy subscription enjoy a base level of <b>free</b> storage included at no additional charge! Additional storage upgrades optionally available. <a href="http://ithemes.com/backupbuddy-stash/" target="_new">Learn more here.</a>',
		//'details'		=>		'', // Instance var. Set on init with quota info, etc.
	);
	
	// Default settings. Should be public static for auto-merging.
	public static $default_settings = array(
		'type'						=>		'stash',	// MUST MATCH your destination slug. Required destination field.
		'title'						=>		'',			// Required destination field.
		
		'itxapi_username'			=>		'',			// Username to connect to iThemes API.
		'itxapi_password'			=>		'',			// Password (hash) to connect to iThemes API.
		
		'directory'					=>		'',			// Subdirectory to put into in addition to the site url directory.
		'ssl'						=>		'1',		// Whether or not to use SSL encryption for connecting.
		'server_encryption'			=>		'AES256',	// Encryption (if any) to have the destination enact. Empty string for none.
		'max_chunk_size'			=>		'0',		// Maximum chunk size in MB. Anything larger will be chunked up into pieces this size (or less for last piece). This allows larger files to be sent than would otherwise be possible. Minimum of 5mb allowed by S3.
		'db_archive_limit'			=>		'10',		// Maximum number of db backups for this site in this directory for this account. No limit if zero 0.
		'full_archive_limit' 		=>		'4',		// Maximum number of full backups for this site in this directory for this account. No limit if zero 0.
		'manage_all_files'			=>		'1',		// Allow user to manage all files in Stash? If enabled then user can view all files after entering their password. If disabled the link to view all is hidden.
		
		// Do not store these for destination settings. Only used to pass to functions in this file.
		'_multipart_id'				=>		'',			// Instance var. Internal use only for continuing a chunked upload.
		'_multipart_partnumber'		=>		0,			// Instance var. Part number to upload next.
		'_multipart_file'			=>		'',			// Instance var. Internal use only to store the file that is currently set to be multipart chunked.
		'_multipart_counts'			=>		array(),	// Instance var. Multipart chunks to send. Generated by S3's get_multipart_counts().
		'_multipart_upload_data'	=>		'',		// Instance var. Contains the Stash upload data returned from the Stash API upload URL. Re-used for multipart sending of parts.
		'_multipart_backup_type_dir' => '',	// Instance var. Backup type subdirectory (if any).
	);
	
	
	
	
	
	/*	send()
	 *	
	 *	Send one or more files.
	 *	
	 *	@param		array			$files			Array of one or more files to send.
	 *	@return		boolean|array					True on success, false on failure, array if a multipart chunked send so there is no status yet.
	 */
	public static function send( $settings = array(), $files = array(), $clear_uploads = false ) {
		
		global $pb_backupbuddy_destination_errors;
		
		if ( !is_array( $files ) ) {
			$files = array( $files );
		}
		
		
		if ( $clear_uploads === false ) { // Uncomment the following line to override and always clear.
			//$clear_uploads = true;
		}
		
		
		$itxapi_username = $settings['itxapi_username'];
		$itxapi_password = $settings['itxapi_password'];
		$db_archive_limit = $settings['db_archive_limit'];
		$full_archive_limit = $settings['full_archive_limit'];
		$max_chunk_size = $settings['max_chunk_size'];
		$remote_path = self::get_remote_path( $settings['directory'] ); // Has leading and trailng slashes.
		if ( $settings['ssl'] == '0' ) {
			$disable_ssl = true;
		} else {
			$disable_ssl = false;
		}
		
		$multipart_id = $settings['_multipart_id'];
		$multipart_counts = $settings['_multipart_counts'];
		
		pb_backupbuddy::status( 'details', 'Stash remote path set to `' . $remote_path . '`.' );
		
		require_once( dirname( __FILE__ ) . '/lib/class.itx_helper.php' );
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		// Stash API talk.
		$stash = new ITXAPI_Helper( pb_backupbuddy_destination_stash::ITXAPI_KEY, pb_backupbuddy_destination_stash::ITXAPI_URL, $itxapi_username, $itxapi_password );
		
		$manage_data = pb_backupbuddy_destination_stash::get_manage_data( $settings );
		//print_r( $manage_data );
		//die();
		
		// Wipe all current uploads.
		if ( $clear_uploads === true ) {
			pb_backupbuddy::status( 'details', 'Clearing any current uploads via Stash call to `abort-all`.' );
			$abort_url = $stash->get_upload_url(null, 'abort-all');
			$request = new RequestCore($abort_url);
			//pb_backupbuddy::status('details', print_r( $request , true ) );
			$response = $request->send_request( true );
		}
		
		// Process multipart transfer that we already initiated in a previous PHP load.
		if ( $multipart_id != '' ) { // Multipart upload initiated and needs parts sent.
			
			// Create S3 instance.
			pb_backupbuddy::status( 'details', 'Creating Stash S3 instance.' );
			$s3 = new AmazonS3( $settings['_multipart_upload_data']['credentials'] );    // the key, secret, token
			if ( $disable_ssl === true ) {
				@$s3->disable_ssl(true);
			}
			pb_backupbuddy::status( 'details', 'Stash S3 instance created.' );
			
			$this_part_number = $settings['_multipart_partnumber'] + 1;
			pb_backupbuddy::status( 'details', 'Stash beginning upload of part `' . $this_part_number . '` of `' . count( $settings['_multipart_counts'] ) . '` parts of file `' . $settings['_multipart_file'] . '` with multipart ID `' . $settings['_multipart_id'] . '`.' );
			$response = $s3->upload_part( $settings['_multipart_upload_data']['bucket'], $settings['_multipart_upload_data']['object'], $settings['_multipart_id'], array(
				'expect'     => '100-continue',
				'fileUpload' => $settings['_multipart_file'],
				'partNumber' => $this_part_number,
				'seekTo'     => (integer) $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ]['seekTo'],
				'length'     => (integer) $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ]['length'],
			));
			
			if(!$response->isOK()) {
				$this_error = 'Stash unable to upload file part for multipart upload `' . $settings['_multipart_id'] . '`. Details: `' . print_r( $response, true ) . '`.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
			}
			
			// Update stats.
			foreach( pb_backupbuddy::$options['remote_sends'] as $identifier => $remote_send ) {
				if ( isset( $remote_send['_multipart_id'] ) && ( $remote_send['_multipart_id'] == $multipart_id ) ) { // this item.
					pb_backupbuddy::$options['remote_sends'][$identifier]['_multipart_status'] = 'Sent part ' . $this_part_number . ' of ' . count( $settings['_multipart_counts'] ) . '.';
					if ( $this_part_number == count( $settings['_multipart_counts'] ) ) {
						pb_backupbuddy::$options['remote_sends'][$identifier]['_multipart_status'] .= '<br>Success.';
						pb_backupbuddy::$options['remote_sends'][$identifier]['finish_time'] = time();
					}
					pb_backupbuddy::save();
					break;
				}
			}
			
			// Made it here so success sending part. Increment for next part to send.
			$settings['_multipart_partnumber']++;
			
			if ( !isset( $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ] ) ) { // No more parts exist for this file. Tell S3 the multipart upload is complete and move on.
				pb_backupbuddy::status( 'details', 'Stash getting parts with etags to notify S3 of completed multipart send.' );
				$etag_parts = $s3->list_parts( $settings['_multipart_upload_data']['bucket'], $settings['_multipart_upload_data']['object'], $settings['_multipart_id'] );
				pb_backupbuddy::status( 'details', 'Stash got parts list. Notifying S3 of multipart upload completion.' );
				$response = $s3->complete_multipart_upload( $settings['_multipart_upload_data']['bucket'], $settings['_multipart_upload_data']['object'], $settings['_multipart_id'], $etag_parts );
				if(!$response->isOK()) {
					$this_error = 'Stash unable to notify S3 of completion of all parts for multipart upload `' . $settings['_multipart_id'] . '`.';
					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				} else {
					pb_backupbuddy::status( 'details', 'Stash notified S3 of multipart completion.' );
				}
				
				// Notify Stash API that things were succesful.
				$done_url = $stash->get_upload_url( $settings['_multipart_file'], 'done', $remote_path . $settings['_multipart_backup_type_dir'] . basename( $settings['_multipart_file'] ) );
				pb_backupbuddy::status( 'details', 'Notifying Stash of completed multipart upload with done url `' . $done_url . '`.' );
				$request = new RequestCore( $done_url );
				$response = $request->send_request(true);
				if(!$response->isOK()) {
					$this_error = 'Error #756834682. Could not finalize Stash upload. Response code: `' . $response->get_response_code() . '`; Response body: `' . $response->get_response_body() . '`; Response headers: `' . $response->get_response_header() . '`.';					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				} else { // Good server response.
					
					// See if we got an optional json response.
					$upload_data = @json_decode($response->body, true );
					if(isset($upload_data['error'])) {
						$this_error = 'Stash error(s): `' . implode( ' - ', $upload_data['error'] ) . '`.';
						$pb_backupbuddy_destination_errors[] = $this_error;
						pb_backupbuddy::status( 'error', $this_error );
						return false;
					}
										
					pb_backupbuddy::status( 'details', 'Stash success sending file `' . basename( $settings['_multipart_file'] ) . '`. File uploaded via multipart across `' . $this_part_number . '` parts and reported to Stash as completed.' );
				}
				
				pb_backupbuddy::status( 'details', 'Stash has no more parts left for this multipart upload. Clearing multipart instance variables.' );
				$settings['_multipart_partnumber'] = 0;
				$settings['_multipart_id'] = '';
				$settings['_multipart_file'] = '';
				$settings['_multipart_counts'] = array();
				$settings['_multipart_upload_data'] = array();
			}
			
			delete_transient( 'pb_backupbuddy_stashquota_' . $settings['itxapi_username'] ); // Delete quota transient since it probably has changed now.
			
			// Schedule to continue if anything is left to upload for this multipart of any individual files.
			if ( ( $settings['_multipart_id'] != '' ) || ( count( $files ) > 0 ) ) {
				pb_backupbuddy::status( 'details', 'Stash multipart upload has more parts left. Scheduling next part send.' );
				pb_backupbuddy::$classes['core']->schedule_single_event( time(), pb_backupbuddy::cron_tag( 'destination_send' ), array( $settings, $files, 'multipart', false ) );
				spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
				update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
				pb_backupbuddy::status( 'details', 'Stash scheduled send of next part(s). Done for this cycle.' );
				
				return array( $settings['_multipart_id'], 'Sent ' . $this_part_number . ' of ' . count( $multipart_destination_settings['_multipart_counts'] . ' parts.' ) );
			}
		}
		
		
		// Upload each file.
		foreach( $files as $file_id => $file ) {
			
			// Determine backup type directory (if zip).
			$backup_type_dir = '';
			$backup_type = '';
			if ( stristr( $file, '.zip' ) !== false ) { // If a zip try to determine backup type.
				pb_backupbuddy::status( 'details', 'Stash: Zip file. Detecting backup type if possible.' );
				$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( $file );
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'] ) ) {
					pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `' . pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'] . '` via integrity check data.' );
					$backup_type_dir = pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'] . '/';
					$backup_type = pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'];
				} else {
					if ( stristr( $file, '-db-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `db` via filename.' );
						$backup_type_dir = 'db/';
						$backup_type = 'db';
					} elseif ( stristr( $file, '-full-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `full` via filename.' );
						$backup_type_dir = 'full/';
						$backup_type = 'full';
					} else {
						pb_backupbuddy::status( 'details', 'Stash: Could not detect backup type via integrity details nor filename.' );
					}
				}
			}
			
			// Interact with Stash API.
			pb_backupbuddy::status( 'details', 'Determining Stash upload URL for `' . $file . '`.` with destination remote path `' . $remote_path . $backup_type_dir . basename( $file ) . '`.' );
			$upload_url = $stash->get_upload_url( $file, 'request', $remote_path . $backup_type_dir . basename( $file ) );
			pb_backupbuddy::status( 'details', 'Determined upload url: `' . $upload_url . '`.' );
			$request = new RequestCore( $upload_url );
			pb_backupbuddy::status( 'details', 'Sending Stash API request.' );
			$response = $request->send_request( true );
			
			// Validate response.
			if(!$response->isOK()) {
				$this_error = 'Stash request for upload credentials failed.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
			}
			if(!$upload_data = json_decode($response->body, true)) {
				$this_error = 'Stash API did not give a valid JSON response.';
				$pb_backupbuddy_destination_errors[] = $this_error;
			    pb_backupbuddy::status( 'error', $this_error );
				return false;
			}
			if(isset($upload_data['error'])) {
				$this_error = 'Stash error(s): `' . implode( ' - ', $upload_data['error'] ) . '`.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
			}
			
			// Calculate meta data to send.
			/*
			$meta_array = array();
			if ( stristr( $file, '.zip' ) !== false ) { // If a zip try to determine backup type.
				pb_backupbuddy::status( 'details', 'Stash: Zip file. Detecting backup type if possible.' );
				$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( $file );
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'] ) ) {
					pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `' . pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'] . '` via integrity check data.' );
					$meta_array['backup_type'] = pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'];
				} else {
					if ( stristr( $file, '-db-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `db` via filename.' );
						$meta_array['backup_type'] = 'db';
					} elseif ( stristr( $file, '-full-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'Stash: Detected backup type as `full` via filename.' );
						$meta_array['backup_type'] = 'full';
					} else {
						pb_backupbuddy::status( 'details', 'Stash: Could not detect backup type via integrity details nor filename.' );
					}
				}
			}
			*/
			
			// Create S3 instance.
			pb_backupbuddy::status( 'details', 'Creating Stash S3 instance.' );
			$s3 = new AmazonS3( $upload_data['credentials'] );    // the key, secret, token
			if ( $disable_ssl === true ) {
				@$s3->disable_ssl(true);
			}
			pb_backupbuddy::status( 'details', 'Stash S3 instance created.' );
			
			
			// Handle chunking of file into a multipart upload (if applicable).
			$file_size = filesize( $file );
			if ( ( $max_chunk_size >= 5 ) && ( ( $file_size / 1024 / 1024 ) > $max_chunk_size ) ) { // minimum chunk size is 5mb. Anything under 5mb we will not chunk.
				
				pb_backupbuddy::status( 'details', 'Stash file size of ' . ( $file_size / 1024 / 1024 ) . 'MB exceeds max chunk size of ' . $max_chunk_size . 'MB set in settings for sending file as multipart upload.' );
				// Initiate multipart upload with S3.
				pb_backupbuddy::status( 'details', 'Initiating Stash multipart upload.' );
				$response = $s3->initiate_multipart_upload(
					$upload_data['bucket'],
					$upload_data['object'],
					array(
						'encryption' => 'AES256',
						//'meta'       => $meta_array,
					)
				);
				
				if(!$response->isOK()) {
					$this_error = 'Stash was unable to initiate multipart upload.';
					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				} else {
					$upload_id = (string) $response->body->UploadId;
					pb_backupbuddy::status( 'details', 'Stash initiated multipart upload with ID `' . $upload_id . '`.' );
				}
				
				// Get chunk parts for multipart transfer.
				pb_backupbuddy::status( 'details', 'Stash getting multipart counts.' );
				$parts = $s3->get_multipart_counts( $file_size, $max_chunk_size * 1024 * 1024 ); // Size of chunks expected to be in bytes.
				
				$multipart_destination_settings = $settings;
				$multipart_destination_settings['_multipart_id'] = $upload_id;
				$multipart_destination_settings['_multipart_partnumber'] = 0;
				$multipart_destination_settings['_multipart_file'] = $file;
				$multipart_destination_settings['_multipart_counts'] = $parts;
				$multipart_destination_settings['_multipart_upload_data'] = $upload_data;
				$multipart_destination_settings['_multipart_backup_type_dir'] = $backup_type_dir;
								
				pb_backupbuddy::status( 'details', 'Stash multipart settings to pass:' . print_r( $multipart_destination_settings, true ) );
				
				unset( $files[$file_id] ); // Remove this file from queue of files to send as it is now passed off to be handled in multipart upload.
				
				// Schedule to process the parts.
				pb_backupbuddy::status( 'details', 'Stash scheduling send of next part(s).' );
				pb_backupbuddy::$classes['core']->schedule_single_event( time(), pb_backupbuddy::cron_tag( 'destination_send' ), array( $multipart_destination_settings, $files, 'multipart', false ) );
				spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
				update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
				pb_backupbuddy::status( 'details', 'Stash scheduled send of next part(s). Done for this cycle.' );
				
				return array( $upload_id, 'Starting send of ' . count( $multipart_destination_settings['_multipart_counts'] ) . ' parts.' );
			} else {
				if ( $max_chunk_size != '0' ) {
					pb_backupbuddy::status( 'details', 'File size of ' . ( $file_size / 1024 / 1024 ) . 'MB is less than the max chunk size of ' . $max_chunk_size . 'MB; not chunking into multipart upload.' );
				} else {
					pb_backupbuddy::status( 'details', 'Max chunk size set to zero so not chunking into multipart upload.' );
				}
			}
			
			
			// SEND file.
			pb_backupbuddy::status( 'details', 'About to put (upload) object to Stash.' );
			$response = $s3->create_object(
				$upload_data['bucket'],
				$upload_data['object'],
				array(
					'fileUpload' => $file,
					'encryption' => 'AES256',
					//'meta'       => $meta_array,
				)
			);
			//  we can also utilize the multi-part-upload to create an object
			//  $response = $s3->create_mpu_object($upload_data['bucket'], $upload_data['object'], array('fileUpload'=>$upload_file)); 
			
			
			// Validate response. On failure notify Stash API that things went wrong.
			if(!$response->isOK()) {
				
			    pb_backupbuddy::status( 'details', 'Sending upload abort.' );
			    $request = new RequestCore($abort_url);
			    $response = $request->send_request(true);
			    
			    $this_error = 'Could not upload to Stash, attempt aborted.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
			    return false;
			} else {
			//	pb_backupbuddy::status( 'details', 'Stash file upload speed: ' . ( $response->header['_info']['speed_upload'] / 1024 / 1024 ) . 'MB/sec. This number may be invalid for small file transfers.' );
				pb_backupbuddy::status( 'details', 'Stash put success. Need to nofity Stash of upload completion. Details: `' . print_r( $response, true ) . '`.' );
			}
			
			delete_transient( 'pb_backupbuddy_stashquota_' . $settings['itxapi_username'] ); // Delete quota transient since it probably has changed now.
			
			// Notify Stash API that things were succesful.
			$done_url = $stash->get_upload_url( $file, 'done', $remote_path . $backup_type_dir . basename( $file ) );
			pb_backupbuddy::status( 'details', 'Notifying Stash of completed upload with done url `' . $done_url . '`.' );
			$request = new RequestCore( $done_url );
			$response = $request->send_request(true);
			if(!$response->isOK()) {
				$this_error = 'Error #247568834682. Could not finalize Stash upload. Response code: `' . $response->get_response_code() . '`; Response body: `' . $response->get_response_body() . '`; Response headers: `' . $response->get_response_header() . '`.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
			} else { // Good server response.
				
				// See if we got an optional json response.
				$upload_data = @json_decode($response->body, true );
				if(isset($upload_data['error'])) { // Some kind of error.
					$this_error = 'Stash error(s): `' . implode( ' - ', $upload_data['error'] ) . '`.';
					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				}
				
				unset( $files[$file_id] ); // Remove from list of files we have not sent yet.
				
				pb_backupbuddy::status( 'details', 'Stash success sending file `' . basename( $file ) . '`. File uploaded and reported to Stash as completed.' );
			}
			
			
			// Enforce archive limits if applicable.
			if ( $backup_type == 'full' ) {
				$limit = $full_archive_limit;
				pb_backupbuddy::status( 'details', 'Stash full backup archive limit of `' . $limit . '` based on destination settings.' );
			} elseif ( $backup_type == 'db' ) {
				$limit = $db_archive_limit;
				pb_backupbuddy::status( 'details', 'Stash database backup archive limit of `' . $limit . '` based on destination settings.' );
			} else {
				$limit = 0;
				pb_backupbuddy::status( 'error', 'Error #54854895. Stash was unable to determine backup type so archive limits NOT enforced for this backup.' );
			}
			if ( $limit > 0 ) {
				
				pb_backupbuddy::status( 'details', 'Stash archive limit enforcement beginning.' );
				// S3 object for managing files.
				$s3_manage = new AmazonS3( $manage_data['credentials'] );
				if ( $disable_ssl === true ) {
					@$s3_manage->disable_ssl(true);
				}
				
				// Get file listing.
				$response_manage = $s3_manage->list_objects($manage_data['bucket'], array('prefix'=> $manage_data['subkey'] . $remote_path . $backup_type_dir ));     // list all the files in the subscriber account
				
				// Create array of backups and organize by date
				$prefix = pb_backupbuddy::$classes['core']->backup_prefix();
				
				// List backups associated with this site by date.
				$backups = array();
				foreach( $response_manage->body->Contents as $object ) {
					
					$file = str_replace( $manage_data['subkey'] . $remote_path . $backup_type_dir, '', $object->Key );
					// Stash stores files in a directory per site so no need to check prefix here! if ( false !== strpos( $file, 'backup-' . $prefix . '-' ) ) { // if backup has this site prefix...
						$backups[$file] = strtotime( $object->LastModified );
					//}
				
				}
				arsort( $backups );
				
				//error_log( 'backups: ' . print_r( $backups, true ) );
				
				pb_backupbuddy::status( 'details', 'Stash found `' . count( $backups ) . '` backups of this type when checking archive limits.' );
				if ( ( count( $backups ) ) > $limit ) {
					pb_backupbuddy::status( 'details', 'More archives (' . count( $backups ) . ') than limit (' . $limit . ') allows. Trimming...' );
					$i = 0;
					$delete_fail_count = 0;
					foreach( $backups as $buname => $butime ) {
						$i++;
						if ( $i > $limit ) {
							pb_backupbuddy::status ( 'details', 'Trimming excess file `' . $buname . '`...' );
							$response = $s3_manage->delete_object( $manage_data['bucket'], $manage_data['subkey'] . $remote_path . $backup_type_dir . $buname );
							if ( !$response->isOK() ) {
								pb_backupbuddy::status( 'details',  'Unable to delete excess Stash file `' . $buname . '`. Details: `' . print_r( $response, true ) . '`.' );
								$delete_fail_count++;
							}
						}
					}
					pb_backupbuddy::status( 'details', 'Finished trimming excess backups.' );
					if ( $delete_fail_count !== 0 ) {
						$error_message = 'Stash remote limit could not delete ' . $delete_fail_count . ' backups.';
						pb_backupbuddy::status( 'error', $error_message );
						pb_backupbuddy::$classes['core']->mail_error( $error_message );
					}
				}
				
				pb_backupbuddy::status( 'details', 'Stash completed archive limiting.' );
				
			} else {
				pb_backupbuddy::status( 'details',  'No Stash archive file limit to enforce.' );
			} // End remote backup limit
			
			
		} // end foreach.
		
		// Success if we made it this far.
		return true;
		
	} // End send().
	
	
	
	/*	test()
	 *	
	 *	Tests ability to write to this remote destination.
	 *	
	 *	@param		array			$settings	Destination settings.
	 *	@return		bool|string					True on success, string error message on failure.
	 */
	public static function test( $settings ) {
		
		$remote_path = self::get_remote_path( $settings['directory'] ); // Has leading and trailng slashes.
		
		// Try sending a file.
		$test_result = self::send( $settings, dirname( __FILE__ ) . '/icon.png', true ); // 3rd param true forces clearing of any current uploads.
		
		// S3 object for managing files.
		$manage_data = pb_backupbuddy_destination_stash::get_manage_data( $settings );
		$s3_manage = new AmazonS3( $manage_data['credentials'] );
		if ( $settings['ssl'] == 0 ) {
			@$s3_manage->disable_ssl(true);
		}
		
		// Delete sent file.
		$response = $s3_manage->delete_object( $manage_data['bucket'], $manage_data['subkey'] . $remote_path . 'icon.png' );
		if ( !$response->isOK() ) {
			pb_backupbuddy::status( 'details',  'Unable to delete test Stash file `icon.png`. Details: `' . print_r( $response, true ) . '`.' );
		}
		
		delete_transient( 'pb_backupbuddy_stashquota_' . $settings['itxapi_username'] ); // Delete quota transient since it probably has changed now.
		
		return $test_result;
	
	} // End test().
	
	
	
	public static function get_quota( $settings, $bypass_cache = false ) {
		
		
		$cache_time = 43200; // 12 hours.
		
		if ( $bypass_cache == false ) {
			$transient = get_transient( 'pb_backupbuddy_stashquota_' . $settings['itxapi_username'] );
			if ( $transient !== false ) {
				pb_backupbuddy::status( 'details', 'Stash quota information CACHED. Returning cached version.' );
				return $transient;
			}
		} else {
			pb_backupbuddy::status( 'details', 'Stash bypassing cached quota information. Getting new values.' );
		}
				
		$itxapi_username = $settings['itxapi_username'];
		$itxapi_password = $settings['itxapi_password'];
		
		require_once( dirname( __FILE__ ) . '/lib/class.itx_helper.php' );
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		$stash = new ITXAPI_Helper( pb_backupbuddy_destination_stash::ITXAPI_KEY, pb_backupbuddy_destination_stash::ITXAPI_URL, $itxapi_username, $itxapi_password );
		
		$quota_url = $stash->get_quota_url();
		
		$request = new RequestCore( $quota_url );
        $response = $request->send_request(true);
        
		// See if the request was successful.
		if(!$response->isOK())
			pb_backupbuddy::status( 'error', 'Stash request for quota failed.' );
		
		// See if we got a json response.
        if(!$quota_data = json_decode($response->body, true))
			pb_backupbuddy::status( 'error', 'Stash did not get valid json response.' );
        
		// Finally see if the API returned an error.
		if(isset($quota_data['error'])) {            
            if ( $quota_data['error']['code'] == '3002' ) {
            	pb_backupbuddy::alert( 'Invalid iThemes.com Member account password. Please verify your password & try again. <a href="http://ithemes.com/member/member.php" target="_new">Forget your password?</a>' );
            } else {
				pb_backupbuddy::alert( implode( ' - ', $quota_data['error'] ) );
			}
			
            return false;
        }
		
		set_transient( 'pb_backupbuddy_stashquota_' . $settings['itxapi_username'], $quota_data, $cache_time );
		
		return $quota_data;
	}
	
	
	
	/*	get_manage_data()
	 *	
	 *	Get the required credentials and management data for managing user files.
	 *	
	 *	@return		false|array			Boolean false on failure. Array of data on success.
	 */
	public static function get_manage_data( $settings ) {
		
		$itxapi_username = $settings['itxapi_username'];
		$itxapi_password = $settings['itxapi_password'];
		
		require_once( dirname( __FILE__ ) . '/lib/class.itx_helper.php' );
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		$stash = new ITXAPI_Helper( pb_backupbuddy_destination_stash::ITXAPI_KEY, pb_backupbuddy_destination_stash::ITXAPI_URL, $itxapi_username, $itxapi_password );
		
		
		//  get the url to use to request management credentials
		//  url looks something like this - http://api.ithemes.com/stash/upload?apikey=sb31kesem0c879m0&expires=1347439653&subscriber=jwooley&signature=VFMbmXb5OorWwqE0iedOCsDtSgs%3D
		$manage_url = $stash->get_manage_url();
		            
		//  create a new RequestCore to get the credentials
		//  NOTE: RequestCore is part of the AWS SDK, it's a generic http request class.
		//        You can use any available function to make the api request, the wordpress http class for example,
		//        or even just the basic file_get_contents()
		
		$request = new RequestCore($manage_url);
		
		//  send the api request
		$response = $request->send_request(true);
		
		//  see if the request was successful
		if(!$response->isOK()) {
			//throw new Exception('Request for management credentials failed.');
			$error = 'Request for management credentials failed.';
			pb_backupbuddy::status( 'error', $error );
			pb_backupbuddy::alert( $error );
			return false;
		}
		
		//  see if we got a json response
		if(!$manage_data = json_decode($response->body, true)) {
			$error = 'Did not get valid JSON response.';
			pb_backupbuddy::status( 'error', $error );
			pb_backupbuddy::alert( $error );
			return false;
		}
		
		//  finally see if the API returned an error
		if(isset($manage_data['error'])) {
			$error = 'Error: ' . implode(' - ', $upload_data['error']);
			pb_backupbuddy::status( 'error', $error );
			pb_backupbuddy::alert( $error );
			return false;
		}
		
		return $manage_data;
	} // End get_manage_data().
	
	
	
	/*	get_remote_path()
	 *	
	 *	Returns the site-specific remote path to store into.
	 *	Slashes (caused by subdirectories in url) are replaced with underscores.
	 *	Always has a leading and trailing slash.
	 *	
	 *	@return		string			Ex: /dustinbolton.com_blog/
	 */
	public static function get_remote_path( $directory = '' ) {
		
		$remote_path = site_url();
		$remote_path = str_ireplace( 'http://', '', $remote_path );
		$remote_path = str_ireplace( 'https://', '', $remote_path );
		$remote_path = str_ireplace( '/', '_', $remote_path );
		$remote_path = str_ireplace( '~', '_', $remote_path );
		
		$remote_path = '/' . trim( $remote_path, '/\\' ) . '/';
		
		$directory = trim( $directory, '/\\' );
		if ( $directory != '' ) {
			$remote_path .= $directory . '/';
		}
		
		return $remote_path;
		
	} // End get_remote_path().
	
	
	
	
	/*	get_quota_bar()
	 *	
	 *	Returns the progress quota bar showing usage.
	 *	
	 *	@return		string			HTML for the quota bar.
	 */
	public static function get_quota_bar( $account_info ) {
		//echo '<pre>' . print_r( $account_info, true ) . '</pre>';
		
		$return = '';
		$return .= '
		<style>
			.outer_progress {
				-moz-border-radius: 4px;
				-webkit-border-radius: 4px;
				-khtml-border-radius: 4px;
				border-radius: 4px;
				
				border: 1px solid #DDD;
				background: #EEE;
				
				max-width: 700px;
				
				margin-left: auto;
				margin-right: auto;
				
				height: 30px;
			}
			
			.inner_progress {
				border-right: 1px solid #85bb3c;
				background: #8cc63f url("' . pb_backupbuddy::plugin_url() . '/destinations/stash/progress.png") 50% 50% repeat-x;
				
				height: 100%;
			}
			
			.progress_table {
				color: #5E7078;
				font-family: "Open Sans", Arial, Helvetica, Sans-Serif;
				font-size: 14px;
				line-height: 20px;
				text-align: center;
				
				margin-left: auto;
				margin-right: auto;
				margin-bottom: 20px;
			}
		</style>';
		
		if ( isset( $account_info['quota_warning'] ) && ( $account_info['quota_warning'] != '' ) ) {
			//echo '<div style="color: red; max-width: 700px; margin-left: auto; margin-right: auto;"><b>Warning</b>: ' . $account_info['quota_warning'] . '</div><br>';
		}
		
		$return .= '
		<div class="outer_progress">
			<div class="inner_progress" style="width: ' . $account_info['quota_used_percent'] . '%"></div>
		</div>
		
		<table align="center" class="progress_table">
		    <tbody><tr align="center">
		        <td style="width: 10%; font-weight: bold; text-align: center">Free Tier</td>
		        <td style="width: 10%; font-weight: bold; text-align: center">Paid Tier</td>        
		        <td style="width: 10%"></td>
		        <td style="width: 10%; font-weight: bold; text-align: center">Total</td>
		        <td style="width: 10%; font-weight: bold; text-align: center">Used</td>
		        <td style="width: 10%; font-weight: bold; text-align: center">Available</td>        
		    </tr>
		    
		    <tr align="center">
		        <td style="text-align: center">' . $account_info['quota_free_nice'] . '</td>
		        <td style="text-align: center">';
		        if ( $account_info['quota_paid'] == '0' ) {
		        	$return .= 'none';
		        } else {
		        	$return .= $account_info['quota_paid_nice'];
		        }
		        $return .= '</td>
		        <td></td>
		        <td style="text-align: center">' . $account_info['quota_total_nice'] . '</td>
		        <td style="text-align: center">' . $account_info['quota_used_nice'] . '</td>
		        <td style="text-align: center">' . $account_info['quota_available_nice'] . '</td>
		    </tr>
		    ';
		    /*
		    <tr>
		        <td colspan="2" style="text-align: center"><a href="http://ithemes.com/member/stash.php" style="color: #C63D05; text-decoration: none;">Upgrade</a></td>
		    </tr>
		    */
		$return .= '
		</tbody></table>';
		
		return $return;
		
	} // End get_quota_bar().
	
} // End class.