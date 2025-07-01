<?php
	namespace Pdsinterop\PhpSolid;
	
	class MailTemplateGenerator {
		public static function mailtemplate($mailTokens) {
			$backgroundColor = MAILSTYLES['container']['backgroundColor'] ?? "#eeeeee";
			ob_start();
?>
<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
 <meta charset="UTF-8" />
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <!--[if !mso]><!-- -->
 <meta http-equiv="X-UA-Compatible" content="IE=edge" />
 <!--<![endif]-->
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
 <meta name="x-apple-disable-message-reformatting" />
 <title>{title}</title>
 <style>
 html, body { margin: 0 !important; padding: 0 !important; min-height: 100% !important; width: 100% !important; -webkit-font-smoothing: antialiased; }
         * { -ms-text-size-adjust: 100%; }
         #outlook a { padding: 0; }
         .ReadMsgBody, .ExternalClass { width: 100%; }
         .ExternalClass, .ExternalClass p, .ExternalClass td, .ExternalClass div, .ExternalClass span, .ExternalClass font { line-height: 100%; }
         table, td, th { mso-table-lspace: 0 !important; mso-table-rspace: 0 !important; border-collapse: collapse; }
         u + .body table, u + .body td, u + .body th { will-change: transform; }
         body, td, th, p, div, li, a, span { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; mso-line-height-rule: exactly; }
         img { border: 0; outline: 0; line-height: 100%; text-decoration: none; -ms-interpolation-mode: bicubic; }
         a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
         .body .pc-project-body { background-color: transparent !important; }
                 
 
         @media (min-width: 621px) {
             .pc-lg-hide {  display: none; } 
             .pc-lg-bg-img-hide { background-image: none !important; }
         }
 </style>
 <style>
 @media (max-width: 620px) {
 .pc-project-body {min-width: 0px !important;}
 .pc-project-container {width: 100% !important;}
 .pc-sm-hide, .pc-w620-gridCollapsed-1 > tbody > tr > .pc-sm-hide {display: none !important;}
 .pc-sm-bg-img-hide {background-image: none !important;}
 .pc-w620-padding-30-30-30-30 {padding: 30px 30px 30px 30px !important;}
 .pc-w620-padding-35-50-35-50 {padding: 35px 50px 35px 50px !important;}
 .pc-w620-padding-10-25-10-25 {padding: 10px 25px 10px 25px !important;}
 table.pc-w620-spacing-0-0-0-0 {margin: 0px 0px 0px 0px !important;}
 td.pc-w620-spacing-0-0-0-0,th.pc-w620-spacing-0-0-0-0{margin: 0 !important;padding: 0px 0px 0px 0px !important;}
 
 .pc-w620-gridCollapsed-1 > tbody,.pc-w620-gridCollapsed-1 > tbody > tr,.pc-w620-gridCollapsed-1 > tr {display: inline-block !important;}
 .pc-w620-gridCollapsed-1.pc-width-fill > tbody,.pc-w620-gridCollapsed-1.pc-width-fill > tbody > tr,.pc-w620-gridCollapsed-1.pc-width-fill > tr {width: 100% !important;}
 .pc-w620-gridCollapsed-1.pc-w620-width-fill > tbody,.pc-w620-gridCollapsed-1.pc-w620-width-fill > tbody > tr,.pc-w620-gridCollapsed-1.pc-w620-width-fill > tr {width: 100% !important;}
 .pc-w620-gridCollapsed-1 > tbody > tr > td,.pc-w620-gridCollapsed-1 > tr > td {display: block !important;width: auto !important;padding-left: 0 !important;padding-right: 0 !important;margin-left: 0 !important;}
 .pc-w620-gridCollapsed-1.pc-width-fill > tbody > tr > td,.pc-w620-gridCollapsed-1.pc-width-fill > tr > td {width: 100% !important;}
 .pc-w620-gridCollapsed-1.pc-w620-width-fill > tbody > tr > td,.pc-w620-gridCollapsed-1.pc-w620-width-fill > tr > td {width: 100% !important;}
 .pc-w620-gridCollapsed-1 > tbody > .pc-grid-tr-first > .pc-grid-td-first,.pc-w620-gridCollapsed-1 > .pc-grid-tr-first > .pc-grid-td-first {padding-top: 0 !important;}
 .pc-w620-gridCollapsed-1 > tbody > .pc-grid-tr-last > .pc-grid-td-last,.pc-w620-gridCollapsed-1 > .pc-grid-tr-last > .pc-grid-td-last {padding-bottom: 0 !important;}
 
 .pc-w620-gridCollapsed-0 > tbody > .pc-grid-tr-first > td,.pc-w620-gridCollapsed-0 > .pc-grid-tr-first > td {padding-top: 0 !important;}
 .pc-w620-gridCollapsed-0 > tbody > .pc-grid-tr-last > td,.pc-w620-gridCollapsed-0 > .pc-grid-tr-last > td {padding-bottom: 0 !important;}
 .pc-w620-gridCollapsed-0 > tbody > tr > .pc-grid-td-first,.pc-w620-gridCollapsed-0 > tr > .pc-grid-td-first {padding-left: 0 !important;}
 .pc-w620-gridCollapsed-0 > tbody > tr > .pc-grid-td-last,.pc-w620-gridCollapsed-0 > tr > .pc-grid-td-last {padding-right: 0 !important;}
 
 .pc-w620-tableCollapsed-1 > tbody,.pc-w620-tableCollapsed-1 > tbody > tr,.pc-w620-tableCollapsed-1 > tr {display: block !important;}
 .pc-w620-tableCollapsed-1.pc-width-fill > tbody,.pc-w620-tableCollapsed-1.pc-width-fill > tbody > tr,.pc-w620-tableCollapsed-1.pc-width-fill > tr {width: 100% !important;}
 .pc-w620-tableCollapsed-1.pc-w620-width-fill > tbody,.pc-w620-tableCollapsed-1.pc-w620-width-fill > tbody > tr,.pc-w620-tableCollapsed-1.pc-w620-width-fill > tr {width: 100% !important;}
 .pc-w620-tableCollapsed-1 > tbody > tr > td,.pc-w620-tableCollapsed-1 > tr > td {display: block !important;width: auto !important;}
 .pc-w620-tableCollapsed-1.pc-width-fill > tbody > tr > td,.pc-w620-tableCollapsed-1.pc-width-fill > tr > td {width: 100% !important;box-sizing: border-box !important;}
 .pc-w620-tableCollapsed-1.pc-w620-width-fill > tbody > tr > td,.pc-w620-tableCollapsed-1.pc-w620-width-fill > tr > td {width: 100% !important;box-sizing: border-box !important;}
 }
 @media (max-width: 520px) {
 .pc-w520-padding-25-25-25-25 {padding: 25px 25px 25px 25px !important;}
 .pc-w520-padding-30-40-30-40 {padding: 30px 40px 30px 40px !important;}
 }
 </style>
 <!--[if mso]>
    <style type="text/css">
        .pc-font-alt {
            font-family: Arial, Helvetica, sans-serif !important;
        }
    </style>
    <![endif]-->
 <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
</head>

<body class="body pc-font-alt" style="width: 100% !important; min-height: 100% !important; margin: 0 !important; padding: 0 !important; font-weight: normal; color: #2D3A41; mso-line-height-rule: exactly; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; font-variant-ligatures: normal; text-rendering: optimizeLegibility; -moz-osx-font-smoothing: grayscale; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
 <table class="pc-project-body" style="table-layout: fixed; width: 100%; min-width: 600px; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>" border="0" cellspacing="0" cellpadding="0" role="presentation">
  <tr>
   <td align="center" valign="top" style="width:auto;">
    <table class="pc-project-container" align="center" style="width: 600px; max-width: 600px;" border="0" cellpadding="0" cellspacing="0" role="presentation">
     <tr>
      <td style="padding: 20px 0px 20px 0px;" align="left" valign="top">
       <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
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
  <!-- BEGIN MODULE: Header -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
   <tr>
    <td valign="top" class="pc-w520-padding-25-25-25-25 pc-w620-padding-30-30-30-30" style="padding: 36px 40px 36px 40px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
       <td align="center" valign="top">
        <img src="https://cloudfilesdm.com/postcards/logo-white.png" width="125" height="25" alt="" style="display: block; outline: 0; line-height: 100%; -ms-interpolation-mode: bicubic; width: 125px; height: auto; max-width: 100%; border: 0;" />
       </td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
  <!-- END MODULE: Header -->
<?php
  		}

		private static function mailTemplateCallToAction($mailTokens) {
			$backgroundColor = MAILSTYLES['call-to-action']['backgroundColor'] ?? "#ffffff";
			$color = MAILSTYLES['call-to-action']['color'] ?? "#181818";
			$colorMuted = MAILSTYLES['call-to-action']['colorMuted'] ?? "#333333";
			$fontFamily = MAILSTYLES['call-to-action']['fontFamily'] ?? "Arial, Helvetica, sans-serif";
?>
  <!-- BEGIN MODULE: Call to action -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
   <tr>
    <td valign="top" class="pc-w520-padding-30-40-30-40 pc-w620-padding-35-50-35-50" style="padding: 40px 60px 40px 60px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
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
  <!-- END MODULE: Call to action -->
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
     <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
       <th valign="top" align="center" style="padding: 0px 0px 30px 0px; text-align: center; font-weight: normal;">
        <!--[if mso]>
<table border="0" cellpadding="0" cellspacing="0" role="presentation" align="center" style="border-collapse: separate; border-spacing: 0; margin-right: auto; margin-left: auto;">
    <tr>
        <td valign="middle" align="center" style="border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; text-align:center; color: <?php echo $buttonTextColor; ?>; padding: 14px 18px 14px 18px; mso-padding-left-alt: 0; margin-left:18px;" bgcolor="<?php echo $buttonBackgroundColor; ?>">
                            <a class="pc-font-alt" style="display: inline-block; text-decoration: none; text-align: center;" target="_blank"><span style="font-size: 20px;mso-line-height-alt:30px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: '<?php echo $fontFamily; ?>;line-height: 150%;">{code}</span></span></span></a>
                        </td>
    </tr>
</table>
<![endif]-->
        <!--[if !mso]><!-- -->
        <a href="{buttonLink}" style="display: inline-block; box-sizing: border-box; border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; padding: 14px 18px 14px 18px; vertical-align: top; text-align: center; text-align-last: center; text-decoration: none; -webkit-text-size-adjust: none;" target="_blank"><span style="font-size: 20px;mso-line-height-alt:30px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: <?php echo $fontFamily; ?>;line-height: 150%;">{buttonText}</span></span></span></span>
        <!--<![endif]-->
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
     <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
       <th valign="top" align="center" style="padding: 0px 0px 30px 0px; text-align: center; font-weight: normal;">
        <!--[if mso]>
<table border="0" cellpadding="0" cellspacing="0" role="presentation" align="center" style="border-collapse: separate; border-spacing: 0; margin-right: auto; margin-left: auto;">
    <tr>
        <td valign="middle" align="center" style="border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; text-align:center; color: <?php echo $buttonTextColor; ?>; padding: 14px 18px 14px 18px; mso-padding-left-alt: 0; margin-left:18px;" bgcolor="<?php echo $buttonBackgroundColor; ?>">
                            <a class="pc-font-alt" style="display: inline-block; text-decoration: none; text-align: center;" target="_blank"><span style="font-size: 20px;mso-line-height-alt:30px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: '<?php echo $fontFamily; ?>;line-height: 150%;">{code}</span></span></span></a>
                        </td>
    </tr>
</table>
<![endif]-->
        <!--[if !mso]><!-- -->
        <span style="display: inline-block; box-sizing: border-box; border-radius: 8px; background-color: <?php echo $buttonBackgroundColor; ?>; padding: 14px 18px 14px 18px; vertical-align: top; text-align: center; text-align-last: center; text-decoration: none; -webkit-text-size-adjust: none;" target="_blank"><span style="font-size: 20px;mso-line-height-alt:30px;line-height: 30px;color:<?php echo $buttonTextColor; ?>;font-weight:500;font-style:normal;display:inline-block;vertical-align: top;"><span style="display:inline-block;"><span style="font-family: <?php echo $fontFamily; ?>;line-height: 150%;">{buttonText}</span></span></span></span>
        <!--<![endif]-->
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
 <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
  <tr>
   <td align="center" valign="top" style="padding: 0px 0px 30px 0px; height: auto;">
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" style="margin-right: auto; margin-left: auto;">
     <tr>
      <td valign="top" align="center">
       <div class="pc-font-alt" style="text-decoration: none;">
        <div style="font-size: 36px;mso-line-height-alt:46.08px;line-height: 46.08px;text-align:center;text-align-last:center;color:<?php echo $color; ?>;letter-spacing:-0.6px;font-weight:800;font-style:normal;">
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
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
  <tr>
   <td align="left" valign="top">
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" align="left">
     <tr>
      <td valign="top" align="left">
       <div class="pc-font-alt" style="text-decoration: none;">
        <div style="font-size: 15px;mso-line-height-alt:21px;line-height: 21px;text-align:left;text-align-last:left;color:<?php echo $colorMuted; ?>;letter-spacing:-0.2px;font-weight:400;font-style:normal;">
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
<!-- BEGIN MODULE: Footer -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
   <tr>
    <td class="pc-w620-spacing-0-0-0-0" width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
     <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
      <tr>
       <td valign="top" class="pc-w620-padding-10-25-10-25" style="padding: 10px 40px 10px 40px; height: unset; background-color: <?php echo $backgroundColor; ?>;" bgcolor="<?php echo $backgroundColor; ?>">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
         <tr>
          <td align="center" valign="top">
           <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%" align="center" style="margin-right: auto; margin-left: auto;">
            <tr>
             <td valign="top" align="center">
              <div class="pc-font-alt" style="text-decoration: none;">
               <div style="font-size: 15px;mso-line-height-alt:21px;line-height: 21px;text-align:center;text-align-last:center;color:<?php echo $color; ?>;letter-spacing:-0.2px;font-weight:400;font-style:normal;">
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