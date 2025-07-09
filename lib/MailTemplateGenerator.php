<?php
	namespace Pdsinterop\PhpSolid;
	
	class MailTemplateGenerator {
		public static function mailtemplate($mailTokens) {
			$backgroundColor = MAILSTYLES['container']['backgroundColor'] ?? "#eeeeee";
			ob_start();
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="UTF-8" />
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <meta http-equiv="X-UA-Compatible" content="IE=edge" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <title>{title}</title>
 <style>
 html, body { margin: 0 !important; padding: 0 !important; min-height: 100% !important; width: 100% !important;}
 </style>
</head>

<body style="width: 100% !important; min-height: 100% !important; margin: 0 !important; padding: 0 !important; font-weight: normal; color: #2D3A41; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
 <table style="table-layout: fixed; width: 100%; min-width: 600px; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>" border="0" cellspacing="0" cellpadding="0">
  <tr>
   <td align="center" valign="top" style="width:auto;">
    <table align="center" style="width: 600px; max-width: 600px;" border="0" cellpadding="0" cellspacing="0">
     <tr>
      <td style="padding: 20px 0px 20px 0px;" align="left" valign="top">
       <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
         <td valign="top">
          <?php self::mailTemplateHeader($mailTokens); ?>
         </td>
        </tr>
        <tr>
         <td valign="top">
          <?php self::mailTemplateCallToAction($mailTokens); ?>
         </td>
        </tr>
        <tr>
         <td valign="top">
          <?php self::mailTemplateFooter($mailTokens); ?>
         </td>
        </tr>
       </table>
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
</body>
</html>
<?php
			$mailTemplate = ob_get_contents();
			ob_end_clean();
			return $mailTemplate;
		}

		private static function mailTemplateHeader($mailTokens) {
			$backgroundColor = MAILSTYLES['header']['backgroundColor'] ?? "#3b3b3b";
?>
  <!-- Header -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr>
    <td valign="top" style="padding: 36px 40px 36px 40px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
       <td align="center" valign="top">
        <!-- Insert Logo image here -->
       </td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
  <!-- /Header -->
<?php
  		}

		private static function mailTemplateCallToAction($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
			$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
			$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
			$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
?>
  <!-- Call to action -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr>
    <td valign="top" style="padding: 40px 60px 40px 60px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
     <?php 
		self::mailTemplateCallToActionTitle($mailTokens);
	 	if (isset($mailTokens['buttonText'])) {
			if (isset($mailTokens['buttonLink'])) {
				self::mailTemplateCallToActionButton($mailTokens);
			} else {
				self::mailTemplateCallToActionButtonNoLink($mailTokens);
			}
	 	}
	 	self::mailTemplateCallToActionDescription($mailTokens);
    ?>
    </td>
   </tr>
  </table>
  <!-- /Call to action -->
<?php
		}
		private static function mailTemplateCallToActionButton($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
			$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
			$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
			$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
			$buttonTextColor = MAILSTYLES['call-to-action']['buttonTextColor'] ?? "#ffffff";
			$buttonBackgroundColor = MAILSTYLES['call-to-action']['buttonBackgroundColor'] ?? "#3c6a87";
?>
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
       <th valign="top" align="center" style="padding: 0px 0px 30px 0px; text-align: center; font-weight: normal;">
        <a href="{buttonLink}" style="display: inline-block; box-sizing: border-box; border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; padding: 14px 18px 14px 18px; vertical-align: top; text-align: center; text-decoration: none;" target="_blank"><span style="font-size: 20px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: <?php echo $fontFamily; ?>;line-height: 150%;">{buttonText}</span></span></span></a>
       </th>
      </tr>
     </table>
<?php
		}

		private static function mailTemplateCallToActionButtonNoLink($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
			$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
			$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
			$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
			$buttonTextColor = MAILSTYLES['call-to-action']['buttonTextColor'] ?? "#ffffff";
			$buttonBackgroundColor = MAILSTYLES['call-to-action']['buttonBackgroundColor'] ?? "#3c6a87";
?>
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
       <th valign="top" align="center" style="padding: 0px 0px 30px 0px; text-align: center; font-weight: normal;">
        <span style="display: inline-block; box-sizing: border-box; border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; padding: 14px 18px 14px 18px; vertical-align: top; text-align: center; text-decoration: none;" target="_blank"><span style="font-size: 20px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: <?php echo $fontFamily; ?>;line-height: 150%;">{buttonText}</span></span></span></span>
       </th>
      </tr>
     </table>
<?php
		}

		private static function mailTemplateCallToActionTitle($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
			$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
			$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
			$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
?>
 <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
   <td align="center" valign="top" style="padding: 0px 0px 30px 0px; height: auto;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-right: auto; margin-left: auto;">
     <tr>
      <td valign="top" align="center">
       <div style="text-decoration: none;">
        <div style="font-size: 36px;line-height: 46px;text-align:center;color:<?php echo $color; ?>;letter-spacing:-0.6px;font-weight:800;font-style:normal;">
         <div><span style="font-family: <?php echo $fontFamily; ?>;line-height: 128%;">{title}</span>
         </div>
        </div>
       </div>
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
<?php
		}

		private static function mailTemplateCallToActionDescription($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
		       	$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
		   	$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
		    	$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
   <td align="left" valign="top">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" align="left">
     <tr>
      <td valign="top" align="left">
       <div style="text-decoration: none;">
        <div style="font-size: 15px;line-height: 21px;text-align:left;color:<?php echo $colorMuted; ?>;letter-spacing:-0.2px;font-weight:400;font-style:normal;">
         <div><span style="font-family: <?php echo $fontFamily; ?>;">{description}</span>
         </div>
        </div>
       </div>
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
<?php
		}
		private static function mailTemplateFooter($mailTokens) {
			$backgroundColor = MAILSTYLES['footer']['backgroundColor'] ?? "#3b3b3b";
			$color = MAILSTYLES['footer']['color'] ?? "#e8ecf0";
			$fontFamily = MAILSTYLES['footer']['fontFamily'] ?? "Arial, Helvetica, sans-serif";	
?>
<!-- Footer -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr>
    <td width="100%" border="0" cellspacing="0" cellpadding="0">
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
       <td valign="top" style="padding: 10px 40px 10px 40px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
         <tr>
          <td align="center" valign="top">
           <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" style="margin-right: auto; margin-left: auto;">
            <tr>
             <td valign="top" align="center">
              <div style="text-decoration: none;">
               <div style="font-size: 15px;line-height: 21px;text-align:center;color:<?php echo $color; ?>;letter-spacing:-0.2px;font-weight:400;font-style:normal;">
                <div><span style="font-family: <?php echo $fontFamily; ?>;">{footer}</span>
                </div>
               </div>
              </div>
             </td>
            </tr>
           </table>
          </td>
         </tr>
        </table>
       </td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
  <!-- END MODULE: Footer -->

<?php		}
	}
?>