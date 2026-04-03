CREATE TABLE IF NOT EXISTS `radiology_ultrasound_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `keywords` varchar(1000) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `Modality` int DEFAULT NULL,
  `charge_id` int DEFAULT NULL,
  `Findings` longtext CHARACTER SET utf32 COLLATE utf32_unicode_ci,
  `Impression` longtext CHARACTER SET utf32 COLLATE utf32_unicode_ci,
  `impression_cat` int DEFAULT '0' COMMENT '0 Not Noteable,1 Noteable',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `template_name` (`template_name`) USING BTREE,
  KEY `title` (`title`) USING BTREE,
  KEY `keywords` (`keywords`(768))
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_unicode_ci;

INSERT IGNORE INTO `radiology_ultrasound_template` (`id`, `template_name`, `keywords`, `title`, `Modality`, `charge_id`, `Findings`, `Impression`, `impression_cat`) VALUES
('1', 'USG LOWER ABDOMEN', NULL, 'USG LOWER ABDOMEN', '1', '12', '<p><strong>BOTH KIDNEYS</strong> are normal in size , shape and position.The corticomedullary diffrentiation is well preserved. No hydronephrosis or calculus or mass lesion seen in the kidneys&nbsp; Ureters show no dilatation in the visualized part.</p>

<p><strong>URINARY BLADDER : </strong>Normal&nbsp; in distention with regular outline and normal wall thickness.No luminal calculus / mass lesion seen Mucosal trabeculations are normal.</p>

<p><strong>Uterus </strong>is normal in size and correspond with the size of nulliparous / multiparous /postmenopausal size of&nbsp; uterus and is anteverted / retroverted .Echopattern and contour of the uterus is normal .Endometrial thickness is&nbsp; &hellip;&hellip;&nbsp; mm</p>

<p>Both adnexa are sonographically normal.No solid or cystic mass lesion /collection noted in the adenexa .Cervix is sonographically normal.No / small amount of free fluid is seen in culdesac.</p>

<p>&nbsp;</p>
', 'Sonographic  findings suggest : Normal study for abovementioned organs / structures .', '0'),
('2', 'USG WHOLE ABDOMEN-Women', NULL, 'USG WHOLE ABDOMEN', '1', '8', '<p><strong>LIVER :&nbsp;</strong>is normal in size measuring 12 cms&nbsp;&nbsp;and shows homogenous &nbsp;echotexture of parenchyma.No focal lesion is seen.No intrahepatic biliary radicles dilatation is seen.Hepatic veins and IVC are een normally..</p>

<p><strong>GALLBLADDER :&nbsp;</strong>is distended and shows anechoic lumen. No calculus&nbsp;is seen in the lumen of gallbladder.Wallthickness of the gallbladder is normal.</p>

<p><strong>C.B.D</strong>. :is normal in size at porta .No obstructive lesion is seen.fggfhghghjjj</p>

<p><strong>PANCREAS :&nbsp;&nbsp;</strong>is normal in size and shows homogenous echotexture&nbsp;of parenchyma.PD is not dilated.No parenchymal lesion is seen.No pancreatic collection is seen.</p>

<p><strong>SPLEEN :</strong>&nbsp;is normal in size and measuring 11.5 cms and shows homogenous echotexture&nbsp;of parenchyma..No wellformed local lesion is seen.</p>

<p><strong>KIDNEYS : </strong>Both kidneys are normal in size and position.Both shows normal parenchymal echotexture. are normal in size ,The corticomedullary diffrentiation is well preserved. No hydronephrosis is seen.No&nbsp;calculus is seen.Right kidney measures 85 x 35 mm and left kidney measures 96 x 36 mm in size.</p>

<p><strong>BOTH URETERS :</strong>&nbsp; are not dilated.Both UVJ&nbsp; are seen normally..</p>

<p>No retroperitoneal adenopathy is seen.No ascitis is seen.</p>

<p><strong>URINARY BLADDER</strong> : is distended&nbsp;&nbsp;with regular outline and shows&nbsp; anechoic lumen..UB &nbsp;wall thickness is normal..No &nbsp;calculus or&nbsp;mass lesion seen .</p>

<p><strong>UTERUS : </strong>measure&nbsp;&nbsp;6.0&nbsp; cm x 3.0&nbsp; cm&nbsp; x 3.0 cm&nbsp; in size&nbsp;&nbsp;and is anteverted in it&rsquo;s position .Echopattern and contour of the uterus is normal .Endometrial thickness is 9.6 mm.</p>

<p><strong>ADNEXA :</strong> Both adnexa are sonographically normal.No solid or cystic mass lesion or collection noted in the adenexa</p>

<p>Cervix is sonographically normal.No&nbsp; free fluid is seen in culdesac.</p>

<p>&nbsp;</p>
', 'Sonographic  findings suggest : Normal study for abovementioned organs / structures .', '0'),
('3', 'USG WHOLE ABDOMEN-Men', NULL, 'USG WHOLE ABDOMEN', '1', '8', '<p><strong>LIVER :&nbsp;</strong>is normal in size measuring 12 cms&nbsp;&nbsp;and shows homogenous &nbsp;echotexture of parenchyma.No focal lesion is seen.No intrahepatic biliary radicles dilatation is seen.Hepatic veins and IVC are een normally..</p>

<p><strong>GALLBLADDER :&nbsp;</strong>is distended and shows anechoic lumen. No calculus&nbsp;is seen in the lumen of gallbladder.Wallthickness of the gallbladder is normal.</p>

<p><strong>C.B.D</strong>. :is normal in size at porta .No obstructive lesion is seen.</p>

<p><strong>PANCREAS :&nbsp;&nbsp;</strong>is normal in size and shows homogenous echotexture&nbsp;of parenchyma.PD is not dilated.No parenchymal lesion is seen.No pancreatic collection is seen.</p>

<p><strong>SPLEEN :</strong>&nbsp;is normal in size and measuring 11.5 cms and shows homogenous echotexture&nbsp;of parenchyma..No wellformed local lesion is seen.</p>

<p><strong>KIDNEYS : </strong>Both kidneys are normal in size and position.Both shows normal parenchymal echotexture. are normal in size ,The corticomedullary diffrentiation is well preserved. No hydronephrosis is seen.No&nbsp;calculus is seen.Right kidney measures 85 x 35 mm and left kidney measures 96 x 36 mm in size.</p>

<p><strong>BOTH URETERS :</strong>&nbsp; are not dilated.Both UVJ&nbsp; are seen normally..</p>

<p>No retroperitoneal adenopathy is seen.No ascitis is seen.</p>

<p><strong>URINARY BLADDER</strong> : is distended&nbsp;&nbsp;with regular outline and shows&nbsp; anechoic lumen..UB &nbsp;wall thickness is normal..No &nbsp;calculus or&nbsp;mass lesion seen .</p>

<p><strong>PROSTATE&nbsp; </strong>appears as a soft echogenic region posterior to urinary bladder and caudad to the seminal vesicles.Prostate is normal in size .It&rsquo;s capsule is regular and&nbsp; margins are welldefined .</p>

<p>&nbsp;</p>
', 'Sonographic findings suggest:Normal study for above mentioned organs / structures.', '0'),
('4', 'W A Female after cholecystectomy', NULL, 'USG WHOLE  ABDOMEN', '1', '8', '<p><strong>LIVER </strong>is normal in size and shape. The general outline is regular and the contour appears normal.The parenchymal echotexture is homogenous and smooth.The biliary radicles and vascular channels appear normal in their caliber.</p>

<p><strong>GALLBLADDER </strong>couldnot be profiled as history of cholicystectomy done about 14 years&nbsp; back is given by patient.</p>

<p><strong>C.B.D.</strong> is normal in it&rsquo;s course and caliber.</p>

<p><strong>PANCREAS &amp; Spleen</strong> are sonographically normal.</p>

<p><strong>BOTH KIDNEYS</strong> are normal in size and position.The corticomedullary diffrentiation is well preserved. No hydronephrosis or calculus or mass lesion seen in the kidneys&nbsp; Ureters show no dilatation in the visualized part.</p>

<p><strong>URINARY BLADDER :</strong> Normal&nbsp; in distention with regular outline and normal wall thickness.No luminal calculus / mass lesion seen Mucosal trabeculations are normal.</p>

<p>No free fluid is seen in the abdomen.</p>

<p><strong>Uterus</strong> is normal in size and measure 6.4 cm x 3.4 cm X 3.2 cm and is anteverted in&nbsp; its&nbsp; position&nbsp;&nbsp; Echopattern&nbsp; and contour of the&nbsp; uterus is normal.Endometrial thickness is 05 mm.</p>

<p>Left adenexa is sonographically normal.No solid or cystic mass seen in adenaxa. Right ovary could not be profiled.</p>

<p>Cervix is sonographically normal.Culdesac&nbsp; is&nbsp; normal . Small amount of&nbsp;&nbsp; free&nbsp; fluid&nbsp; is&nbsp; seen in it.</p>
', 'Sonographic  findings suggest : USG finding are suggestive of normal study ( cholicystectomy done about 14 years back )
ADVICE : C T Scan  Abdomen for further exploration of the case', '0'),
('5', 'WA MALE APPENDICULAR LUMP', NULL, 'USG WHOLE ABDOMEN', '1', '8', '<p><strong>LIVER :&nbsp;</strong>is normal in size measuring 12 cms&nbsp;&nbsp;and shows homogenous &nbsp;echotexture of parenchyma.No focal lesion is seen.No intrahepatic biliary radicles dilatation is seen.Hepatic veins and IVC are een normally..</p>

<p><strong>GALLBLADDER :&nbsp;</strong>is distended and shows anechoic lumen. No calculus&nbsp;is seen in the lumen of gallbladder.Wallthickness of the gallbladder is normal.</p>

<p><strong>C.B.D</strong>. :is normal in size at porta .No obstructive lesion is seen.</p>

<p><strong>PANCREAS :&nbsp;&nbsp;</strong>is normal in size and shows homogenous echotexture&nbsp;of parenchyma.PD is not dilated.No parenchymal lesion is seen.No pancreatic collection is seen.</p>

<p><strong>SPLEEN :</strong>&nbsp;is normal in size and measuring 11.5 cms and shows homogenous echotexture&nbsp;of parenchyma..No wellformed local lesion is seen.</p>

<p><strong>KIDNEYS : </strong>Both kidneys are normal in size and position.Both shows normal parenchymal echotexture. are normal in size ,The corticomedullary diffrentiation is well preserved. No hydronephrosis is seen.No&nbsp;calculus is seen.Right kidney measures 85 x 35 mm and left kidney measures 96 x 36 mm in size.</p>

<p><strong>BOTH URETERS :</strong>&nbsp; are not dilated.Both UVJ&nbsp; are seen normally..</p>

<p>No retroperitoneal adenopathy is seen.No ascitis is seen.</p>

<p><strong>URINARY BLADDER</strong> : is distended&nbsp;&nbsp;with regular outline and shows&nbsp; anechoic lumen..UB &nbsp;wall thickness is normal..No &nbsp;calculus or&nbsp;mass lesion seen .</p>

<p>Appendicular lump is seen ( size 3.17 cm x 2.97 cm )</p>

<p>&nbsp;</p>
', 'Sonographic findings suggest: Appendicular Lump', '0'),
('6', 'WAF  after hysterectomy', NULL, 'USG FOR UPPER  ABDOMEN', '1', '13', '<p><strong>LIVER </strong>is normal in size and shape. The general outline is regular and the contour appears normal.The parenchymal echotexture is homogenous and smooth.The biliary radicles and vascular channels appear normal in their caliber.</p>

<p><strong>GALLBLADDER </strong>is normal in size shape and position.Wallthickness of the gallbladder is normal.No calculi is seen in the lumen of gallbladder.</p>

<p><strong>C.B.D.</strong> is normal in it&rsquo;s course and caliber.PANCREAS &amp; spleen are normal in size,shape and echopattern.</p>

<p><strong>BOTH KIDNEYS</strong> are normal in size , shape and position.The corticomedullary diffrentiation is well preserved. No hydronephrosis or calculus or mass lesion seen in the kidneys&nbsp; Ureters show no dilatation in the visualized part.</p>

<p>No free fluid is seen in the abdomen.</p>

<p><strong>Urinary bladder :</strong> Normal&nbsp; in distention with regular outline and normal wall thickness.No luminal calculus / mass lesion is noted . Mucosal trabeculations are normal.</p>

<p>Uterus couldnot be profiled (&nbsp; history of surgical removal of the uterus about 15 years back is given by patient )<br />
&nbsp;<br />
No solid or cystic mass seen in the pelvic region during scanning.</p>
', 'Sonographic  findings suggest normal study for abovementioned organs / structures.', '0'),
('7', 'WAF Fatty Liver', NULL, 'USG WHOLE  ABDOMEN', '1', '8', '<p><strong>LIVER </strong>is normal in size a.The general outline is regular and the contour appears normal.TEchogenecity of liver is increased .Intrahepatic&nbsp; biliary channels are not dilated..PV and hepatic veins are not dilated..</p>

<p><strong>GALLBLADDER </strong>is normal in size shape and position.Wallthickness of the gallbladder is normal.No calculi is seen in the lumen of gallbladder.</p>

<p><strong>C.B.D.</strong> is normal in it&rsquo;s course and caliber..</p>

<p><strong>PANCREAS &amp; spleen</strong> are normal in size,shape and echopattern.</p>

<p><strong>BOTH KIDNEYS</strong> are normal in size , shape and position.The corticomedullary diffrentiation is well preserved. No hydronephrosis or calculus or mass lesion seen in the kidneys&nbsp; Ureters show no dilatation in the visualized part.</p>

<p><strong>Urinary bladder </strong>:No luminal calculus / mass lesion is noted . Mucosal trabeculations are normal.</p>

<p><strong>Uterus </strong>is normal in size&nbsp; ( 6.8&nbsp; cm x 3.5&nbsp; cm&nbsp; x 3.4 cm ) and is anteverted in it&rsquo;s position .Echopattern and contour of the uterus is normal .Endometrial thickness is 06&nbsp; mm.</p>

<p>Both adnexa are sonographically normal.No solid or cystic mass lesion /collection noted in the adenexa .Cervix is sonographically normal.No&nbsp; free fluid is seen in culdesac.</p>

<p>&nbsp;</p>
', 'Sonographic  findings suggest : Mild fatty Liver', '0'),
('8', 'missed abortion 6-7 weeks', NULL, 'USG ROUTINE', '1', '0', '<p>On examination uterus is prominent and normally oriented.In the uterine cavity there is evidence of a welldefined gestational sac .Mean gestational sac diameter corresponds to gestational age about 07 weeks 01 day +/- 04 days &nbsp;with no viable embryo or yolk sac in it Minimal amorphic luminal debris is seen.No cardiac activity is traceable.</p>

<p>No adnexal &nbsp;mass lesion /collection or gestation noted.</p>

<p>Culdesac &nbsp;is &nbsp;normal . No &nbsp;free &nbsp;fluid &nbsp;is &nbsp;seen.</p>

<p><br />
<strong>*NOTE:</strong>It is well documented that on abdominal sonography, the earliest detection of gestational sac can be made at approximately &nbsp;weeks and cardiac activity is seen &nbsp;in an embryo &nbsp;around 6-7 weeks.An absence of yolk sac / embryo in a gestational sac about 12 mm size and / or absence of cardiac activity in embryo beyond 7 weeks is associated with poor outcome/ missed abortion.Also it has &nbsp;been observed that in rare cases above sign of viability may not appear till quite late.So please correlate with clinical findings and other investigation and review after 10-15 days if clinically suggested.&nbsp;</p>

<p><strong>DECLARATION</strong>:- &nbsp;I have neither detected nor disclosed the sex of foetus of pregnant woman to anybody in any manner .<br />
&nbsp;</p>
', '<p>U.S.G.findings suggests EARLY GESTATION WITH NO EMBRYO /YOLK SAC - missed abortion .</p>
', '0'),
('9', 'MISSED ABORTION 9-10 weeks', NULL, 'USG ROUTINE OBS', '1', '3585', '<p>On examination uterus is prominent and normally oriented. A gestational sac is seen in the uterus with a small foetus iin the sac. But no cardiac pulsations or foetal body movements seen during scanning. Estimated gestational age is about &hellip;&hellip;&hellip;&hellip; weeks + - &hellip;&hellip;&hellip;&hellip;. days .</p>

<p>No adnexal &nbsp;mass lesion /collection is noted.Culdesac &nbsp;is &nbsp;normal .</p>

<p><strong>*NOTE:</strong>It is well documented that on abdominal sonography, the earliest detection of gestational sac can be made at approximately &nbsp;5 &ndash; 6 weeks and cardiac activity is seen &nbsp;in an embryo &nbsp;around 6 weeks. Absence of cardiac activity in embryo beyond 6 weeks is associated with poor outcome/ missed abortion.Also it has &nbsp;been observed that in rare cases above sign of viability may not appear till quite late.So please correlate with clinical findings and other investigation and review after 10-15 days if clinically suggested.&nbsp;</p>

<p><strong>DECLARATION</strong>:- &nbsp;I have neither detected nor disclosed the sex of foetus of pregnant woman to anybody in any manner&nbsp;</p>
', 'U.S.G.findings  suggests - missed abortion .', '0'),
('10', 'Left Renal Stone', NULL, 'Whole Abdomen', '1', '8', '<p><strong>LIVER</strong> is normal in size and shape. The general outline is regular and the contour appears normal.The parenchymal echotexture is homogenous and smooth.The biliary radicles and vascular channels appear normal in their caliber.</p>

<p><strong>GALLBLADDER</strong> is normal in size shape and position.Wallthickness of the gallbladder is normal.No calculi is seen in the lumen of gallbladder.C.B.D. is normal in it&rsquo;s course and caliber.</p>

<p><strong>PANCREAS &amp; spleen</strong> are normal in size,shape and echopattern.</p>

<p><strong>LEFT &nbsp;KIDNEY</strong> is normal in size.Outline of the kidney is welldefined .Two small hyperechoic shadows measuring 4.0 mm &amp; 4.7 &nbsp;mm in their diameter &nbsp;are seen in the left kidney and casting it&rsquo;s &nbsp;distal acoustic shadowing . No hydronephrosis is also seen. Left ureter shows no dilatation in the visualized part</p>

<p><strong>RIGHT KIDNEY</strong> is normal.Outline of the kidney are welldefined .Corticomedullary differentiation is well preserved.No hydronephrosis is seen in right &nbsp;kidney. Right ureter shows no dilatation in its visualized part.</p>

<p><strong>URINARY BLADDER</strong> : Normal &nbsp;in distention with regular outline and normal wall thickness.No hyperechoic shadow is seen in the urinary bladder</p>

<p><strong>NO</strong> &nbsp;free fluid is seen in the abdomen.</p>

<p><br />
&nbsp;</p>
', 'Sonographic findings suggest : - Two    small hyperechoic shadows  in the left  kidney which is suggestive of ?? small left renal calculus

ADVICE : I.V.P. for confirmation of sonographic findings  ,final diagnosis and further exploration  of the case.
', '0'),
('11', 'G.A  REPORT  36  WEEKS ', NULL, 'USG ROUTINE OBS', '1', '3585', '<p>*Single live &nbsp;foetus &nbsp;of 36 weeks 00 days +/-07 days ( mean gestational age based on various foetal growth parameters) is seen&nbsp;in longitudinal lie and cephalic presentation at the time of scanning .</p>

<p>*Skull and visualized spine appears normal. No obvious craniospinal anomally is noted at the time of scanning .</p>

<p>*Placenta is seen mainly&nbsp; anterior &amp; fundal with grade II maturity.Internal o.s. is closed . Retroplacental area is clear.The lower edge of the placenta is &nbsp;not reaching &nbsp; upto internal o.s..</p>

<p>*Amniotic fluid is adequate and uniformally distributed&nbsp;( A.F.I. is&nbsp; &nbsp; &nbsp; &nbsp; cm ).Foetal movements noted at the time of scanning are normal.</p>

<p>*Foetal heart rate is 140 &nbsp;beats /&nbsp;minute&nbsp; &nbsp;within normal range and is regular at the time of scanning.</p>

<p>* Cervical length is adequate . Internal O.S.is closed.</p>

<p>EDD (CUA) &nbsp; &nbsp;01-11-2024 &nbsp; &nbsp; &nbsp;EDD (LMP) 27-10-2024 &nbsp; &nbsp; EFW &ndash; 2825 &nbsp;Grams +/- 423 Grams</p>

<p>This &nbsp;is &nbsp;a &nbsp;routine &nbsp;obstetric u / s examination &nbsp;mainly &nbsp;done for estimation of foetal age , general&nbsp;wellbeing, amount of liquor, &nbsp;placenta &nbsp;position &nbsp;etc and not for detailed evaluation of congenital&nbsp;anomalies &nbsp;. All the &nbsp; anomalies &nbsp;may &nbsp;not &nbsp;be &nbsp; appearent &nbsp; during &nbsp; single scanning. U/ S &nbsp;examination &nbsp; does &nbsp; not &nbsp; gurantee &nbsp; a &nbsp; normal &nbsp; baby.</p>

<p>&nbsp;<br />
<strong>ADVICE &nbsp;:-</strong> &nbsp;USG OBSTETRICS ( 3 D &ndash; 4D ) Level II &nbsp;Scan is recommended to detect the &nbsp;congenital abnormalities and detailed examination of the foetus.<br />
<u><strong>DECLARATION :</strong></u>-I Dr Virendra Verma have examined this patient Smt Sheetal sonographically for foetal wellbeing &nbsp;I have neither detected nor disclosed the sex of foetus of pregnant woman to anybody in any manner&nbsp;<br />
&nbsp;</p>
', 'IMPRESSION : Single live foetus', '0'),
('12', 'BILATERAL URETRIC OBSTRUCTION', NULL, 'Whole Abdomen', '1', '50', '<p>LIVER is normal in size and shape. Intrahepatic blood vessels and biliary channels appear normal in caliber.No solid or cystic mass seen in the liver.<br />
GALLBLADDER is normal in size shape and position.Wallthickness of the gallbladder is normal.No calculi is seen in the lumen of gallbladder.C.B.D. is normal in it&rsquo;s course and caliber.PANCREAS &amp; spleen are normal in size,shape and echopattern.<br />
RIGHT KIDNEY &nbsp;is normal in size, shape and position.Mild hydronephrosis is seen in the right kidney. No calculus seen in right kidney. Mild hydroureter &nbsp;is also seen in the visible part of right ureter.No calculus is seen in the visible part of right ureter.<br />
LEFT KIDNEY &nbsp;is normal in size, shape and position.Mild hydronephrosis is seen in the left kidney. No calculus is seen in the left kidney. Mild hydroureter &nbsp;is also seen in the visible part of left ureter.No calculus is seen in the visible part of right ureter.<br />
Urinary bladder is normal.No mass or calculus is seen in the urinary bladder.No free fluid is seen in the abdomen.<br />
Uterus is normal in size and measure 6.4 cm x 3.4 cm x3.1 cm. &nbsp;Echotexture and contour of the uterus is normal and is anteverted &nbsp;in it&rsquo;s position. No focal lesion seen in the uterus .Endometrial thickness is 18 mm.&nbsp;<br />
Both adnexa are sonographically normal.No solid or cystic mass lesion /collection noted in the adenexa .Cervix is sonographically normal.Culdesac &nbsp;is &nbsp;normal . &nbsp;N0 &nbsp; free &nbsp;fluid &nbsp;is &nbsp;seen in it.<br />
IMPRESSION : U.S.G.findings &nbsp; suggest :- Bilateral uretric obstruction &ndash; cause ? uretric calculus<br />
ADVICE : I.V.P &amp; other relevant investigations for confirmation of ultrasonographic findings , cause of the obstruction and further exploration &amp; management of the case .<br />
s</p>
', '', '0'),
('17', 'ctscan', NULL, 'ctscan', '4', '3887', '<p>test</p>
', 'test', '0'),
('18', 'x-ray', NULL, 'test', '3', '2', '<p>test <strong>data</strong></p>
', '<p><strong>test </strong>data</p>
', '0');

