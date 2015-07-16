<?php

// Set this variable with your domain
$yourDomain = "www.yourdomain.com";

function validateEmail( $email, $domainCheck = true, $verify = true )
{
  global $yourDomain;

  $output = array(
    "valid" => true,
    "message" => "The email is valid!"
  );

  // Check email syntax using regular expression
  if ( preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $email, $matches) )
  {
    $user = $matches[1];
    $domain = $matches[2];

    // Check record MX
    if ( $domainCheck && function_exists('checkdnsrr') )
    {
      // Create array with available mail servers
      if ( getmxrr($domain, $mxhosts, $mxweight) )
      {
        for ( $i = 0; $i < count($mxhosts); $i++ )
        {
          $mxs[$mxhosts[$i]] = $mxweight[$i];
        }

        asort($mxs);
        $mailers = array_keys($mxs);

      }
      else if ( checkdnsrr($domain, 'A') )
      {
        $mailers[0] = gethostbyname($domain);
      }
      else
      {
        $mailers = array();
      }

      $total = count( $mailers );

      // Query each mailserver
      if ( $total > 0 && $verify )
      {
        // Check mailserver accept mail
        for ( $n = 0; $n < $total; $n++ )
        {
          // Socket settings
          $connect_timeout = 2;
          $errno = 0;
          $errstr = 0;

          $probe_address = 'email@' . $yourDomain;

          // Check socket connection
          if ( $sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout) )
          {
            $response = fgets($sock);
            stream_set_timeout($sock, 45);
            $meta = stream_get_meta_data($sock);

            $cmds = array(
              "HELO " . $yourDomain,
              "MAIL FROM: <" . $probe_address . ">",
              "RCPT TO: <" . $email . ">",
              "QUIT",
            );

            // Connection error
            if ( !$meta['timed_out'] && !preg_match('/^2\d\d/', $response) )
            {
              $output['valid'] = false;
              $output['message'] = "Error: " . $mailers[$n] . " says " . $response . "\n";
              break;
            }

            foreach ( $cmds as $cmd )
            {
              $before = microtime(true);
              fputs($sock, "$cmd\r\n");

              $response = fgets($sock, 4096);
              $t = 1000 * (microtime(true) - $before);

              if ( !$meta['timed_out'] && preg_match('/^5\d\d/', $response) )
              {
                $output['valid'] = false;
                $output['message'] = "Email is not verified: " . $mailers[$n] . " says " . $response . "\n";
                break 2;
              }
            }

            fclose($sock);
            break;
          }
          else if ( $n == $total-1 )
          {
            $output['valid'] = false;
            $output['message'] = $domain . ": No mail server for the specified domain";
          }
        }
      }
      else if ( $total <= 0 )
      {
        $output['valid'] = false;
        $output['message'] = "There is no DNS record for the domain " . $domain;
      }
    }
  }
  else
  {
    $output['valid'] = false;
    $output['message'] = "Syntax error";
  }

  return json_encode( $output );
}

echo validateEmail( $_GET['email'] );

?>
