CREATE TABLE IF NOT EXISTS `lab_repo` (
  `mstRepoKey` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) NOT NULL,
  `RTFData` longtext,
  `HTMLData` longtext,
  `GrpKey` int NOT NULL,
  `IncludeHeader` tinyint(1) DEFAULT '1',
  `IncludeFooter` tinyint(1) DEFAULT '1',
  `charge_id` int DEFAULT '0',
  PRIMARY KEY (`mstRepoKey`),
  UNIQUE KEY `Title` (`Title`),
  KEY `GrpKey` (`GrpKey`),
  KEY `charge_id` (`charge_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

INSERT IGNORE INTO `lab_repo` (`mstRepoKey`, `Title`, `RTFData`, `HTMLData`, `GrpKey`, `IncludeHeader`, `IncludeFooter`, `charge_id`) VALUES
('0', 'Manual', NULL, NULL, '0', '1', '1', '0'),
('1', 'COMPLETE BLOOD COUNT (CBC) ', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td><strong>Haemoglobin</strong></td>
			<td>{HB}</td>
			<td>gm%</td>
			<td>12-16</td>
		</tr>
		<tr>
			<td><strong>TLC</strong></td>
			<td>{TLC}</td>
			<td>per cu mm</td>
			<td>4000-11000</td>
		</tr>
		<tr>
			<td><strong>DLC</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><strong>Neutrophils</strong></td>
			<td>{NEU}</td>
			<td>%</td>
			<td>45-70</td>
		</tr>
		<tr>
			<td><strong>Lymphocytes</strong></td>
			<td>{LYMP}</td>
			<td>%</td>
			<td>20-45</td>
		</tr>
		<tr>
			<td><strong>Eosinophils</strong></td>
			<td>{EOSIN}</td>
			<td>%</td>
			<td>1-8</td>
		</tr>
		<tr>
			<td><strong>Monocytes</strong></td>
			<td>{MONO}</td>
			<td>%</td>
			<td>1-6</td>
		</tr>
		<tr>
			<td><strong>Basophils</strong></td>
			<td>{BASO}</td>
			<td>%</td>
			<td>&lt; 1%</td>
		</tr>
		<tr>
			<td><strong>RBC&nbsp;Count</strong></td>
			<td>{RBC}</td>
			<td>Million/cm</td>
			<td>4.2-5.5</td>
		</tr>
		<tr>
			<td><strong>PCV</strong></td>
			<td>{PCV}</td>
			<td>%</td>
			<td>40 - 54</td>
		</tr>
		<tr>
			<td><strong>MCV</strong></td>
			<td>{MCV}</td>
			<td>fl</td>
			<td>80-100</td>
		</tr>
		<tr>
			<td><strong>MCH</strong></td>
			<td>{mch}</td>
			<td>pg</td>
			<td>28-35</td>
		</tr>
		<tr>
			<td><strong>MCHC</strong></td>
			<td>{MCHC}</td>
			<td>g/dl</td>
			<td>30-38</td>
		</tr>
		<tr>
			<td><strong>Platelet Count</strong></td>
			<td>{PLATELET}</td>
			<td>Lakh per cu mm</td>
			<td>1.5-4.5</td>
		</tr>
		<tr>
			<td><strong>ESR</strong></td>
			<td>{ESR1}</td>
			<td>Mm for 1st hr.</td>
			<td>&lt; 9 &gt;</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '382'),
('2', 'LIVER FUNCTION TEST (LFT) ,SERUM', NULL, '<p>&nbsp;(Spectrophotometry) &nbsp;</p>

<table border="0" cellpadding="3" cellspacing="0" style="width:800px">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td>Bilirubin (Total)</td>
			<td>{SERBIL}</td>
			<td>mg/dl</td>
			<td>0.3-1.2</td>
		</tr>
		<tr>
			<td>Bilirubin (Direct)</td>
			<td>{BD}</td>
			<td>mg/dl</td>
			<td>&lt; 0.3</td>
		</tr>
		<tr>
			<td>Bilirubin (Indirect)</td>
			<td>{BI}</td>
			<td>mg/dl</td>
			<td>&lt; 0.8</td>
		</tr>
		<tr>
			<td>SGOT / Aspartate Aminotransferase (AST)</td>
			<td>{SGOT}</td>
			<td>U/L</td>
			<td>&lt; 35</td>
		</tr>
		<tr>
			<td>SGPT/ Alanine Aminotransferase (ALT)</td>
			<td>{SGPT}</td>
			<td>U/L</td>
			<td>&lt; 40</td>
		</tr>
		<tr>
			<td>Alkaline Phosphatase (Total)</td>
			<td>{SALKALINE}</td>
			<td>U/L</td>
			<td>25-140</td>
		</tr>
		<tr>
			<td>Protein(Total)</td>
			<td>{TOTPRO}</td>
			<td>gm/dl</td>
			<td>6.2-8.0</td>
		</tr>
		<tr>
			<td>Albumin</td>
			<td>{ALBU}</td>
			<td>gm/dl</td>
			<td>3.8-5.4</td>
		</tr>
		<tr>
			<td>Globulin</td>
			<td>{GLOB}</td>
			<td>gm/dl</td>
			<td>1.8-3.6</td>
		</tr>
		<tr>
			<td>A:G Ratio</td>
			<td>{AGR}</td>
			<td>&nbsp;</td>
			<td>1.1-2.0</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '286'),
('3', 'RENAL FUNCTION TEST (KFT)', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td>Urea</td>
			<td>{BU}&nbsp;</td>
			<td>mg/dL</td>
			<td>11-37</td>
		</tr>
		<tr>
			<td>Creatinine</td>
			<td>{SC}</td>
			<td>mg/dL</td>
			<td>0.6-1.4</td>
		</tr>
		<tr>
			<td>Uric Acid</td>
			<td>{SUA}</td>
			<td>mg/dL</td>
			<td>2.6-7.2</td>
		</tr>
		<tr>
			<td>Calcium</td>
			<td>{SCA}</td>
			<td>mg/dL</td>
			<td>8.6-10.3</td>
		</tr>
		<tr>
			<td>Sodium</td>
			<td>{SS}</td>
			<td>m Mol /L</td>
			<td>135-155</td>
		</tr>
		<tr>
			<td>Potassium&nbsp;</td>
			<td>{SP}</td>
			<td>m Mol /L</td>
			<td>3.5-5.5</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '289'),
('4', 'C-Reactive Protein(C.R.P.)', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey"><strong>Result</strong></td>
			<td style="background-color:lightgrey"><strong>Unit</strong></td>
			<td style="background-color:lightgrey"><strong>Bio.Ref. Interval&nbsp;</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">C-Reactive Protein(C.R.P.)<br />
			&nbsp;</td>
			<td style="vertical-align:top">{CRP}<br />
			{CRPSTATUS}</td>
			<td style="vertical-align:top">mg/l</td>
			<td style="vertical-align:top">Adults : &lt; 6 mg/L<br />
			New Bron Baby&nbsp; upto 3 weeks :&nbsp; &lt;4.1 mg/L<br />
			Infants and Children : &lt;2.8 mg/L</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '387'),
('5', 'F.N.A.C.', NULL, '<h3><em><strong>CYTOPATHOLOGY</strong></em></h3>

<h3>F.N.A.C.</h3>

<p>{BPART}</p>

<p><strong>REPORT :</strong></p>

<p>&nbsp;</p>
', '5', '1', '1', '351'),
('6', 'Anti - HIV (I & II) ANTIBODIES', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top"><strong>Result</strong></td>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top"><strong>Method</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Anti - HIV I &amp;&nbsp;II<br />
			ANTIBODIES</td>
			<td style="text-align:left; vertical-align:top">{HIV1}</td>
			<td style="text-align:left; vertical-align:top">IMMUNOCHROMATOGRAPHY</td>
		</tr>
	</tbody>
</table>

<p><strong>INTERPRETATION :</strong> &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
1.This is only a screening test.All reactive sample should be confirmatory test. &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
2.A negative result implies that no Anti HIV &ndash; II antibodies have been detected in the sample by this method. &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
3.A positive result suggests the possibilities of HIV-I and / or HIV-II infection.However these results must be verified by a confirmatory test (IFA / WESTERN BLOT I-II) before pronouncing the patient positive for HIV-I and / r HIV-II infection.&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;</p>
', '12', '1', '1', '295'),
('7', 'HBsAg', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="height:82px; width:100%">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf; height:15pt; text-align:left; vertical-align:top; white-space:nowrap; width:91pt"><strong>Test&nbsp; Name</strong></td>
			<td style="background-color:#bfbfbf; height:15pt; text-align:left; vertical-align:top; white-space:nowrap; width:63pt"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf; height:15pt; text-align:left; vertical-align:top; white-space:normal; width:198pt"><strong>Method</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">HBsAg*<br />
			(AUSTRALIA ANTIGEN)<br />
			&nbsp;</td>
			<td style="vertical-align:top">{HBsAG}</td>
			<td style="vertical-align:top">IMMUNOCHROMATOGRAPHY</td>
		</tr>
	</tbody>
</table>

<p>Note: This is only a screening test. All reactive sample should be confirmatory test.Therefore for a definitive diagnosis,&nbsp;&nbsp; &nbsp;the patient&rsquo;s clinical history, symptomatology as well as serological data,should be considered.Additional follow up testing&nbsp;&nbsp;using available clinical methods(along with repeat card test)is required,if card test is non-reactive with persisting clinical symptoms.False positive results can be obtained due to the presence of other antigens or elevated levels of RF factor.The presence of HBsAg in the serum indicates either a chronic or acute infection with virus,HBeAG may be advised for further evaluation.</p>
', '12', '1', '1', '336'),
('8', 'Serum Prolactin', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Serum Prolactin</td>
			<td>{SProlactin}</td>
			<td>ng/ml</td>
			<td>5-35</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '13', '1', '1', '225'),
('9', 'Vitamin D', NULL, '<h3>Bio-Chemistry</h3>

<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td>Test Name</td>
			<td>Observed Values</td>
			<td>Unit</td>
			<td>Normal Values</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Vitamin D</td>
			<td style="text-align:center; vertical-align:top">{VITD}</td>
			<td style="text-align:left; vertical-align:top">ng/ml</td>
			<td style="text-align:left; vertical-align:top"><strong>Reference Range:</strong><br />
			<em>Deficent : &lt; 20<br />
			Insufficent : 20-29<br />
			Sufficent : 30-100<br />
			Potential Toxicity &gt; 100</em></td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '13', '1', '1', '162'),
('10', 'Glycosylated Hemoglobin (HbA1c)*,EDTA BLOOD', NULL, '<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>
			<p><strong>Test Name</strong></p>
			</td>
			<td>
			<p><strong>Result</strong></p>
			</td>
			<td>
			<p><strong>Unit</strong></p>
			</td>
			<td>
			<p><strong>Bio.Ref.Interval</strong></p>
			</td>
			<td>
			<p><strong>Method</strong></p>
			</td>
		</tr>
		<tr>
			<td>
			<p><strong>GLYCOSYLATED HAEMOGLOBIN (HBA1C) *</strong>, EDTA BLOOD<br />
			Glycosylated Haemoglobin (HbA1c)</p>
			</td>
			<td>
			<p>{HBA1C}</p>
			</td>
			<td>
			<p>%</p>
			</td>
			<td>
			<p>&lt; 5.7 Non diabetic<br />
			adults&gt;=18 years<br />
			5.7-6.4 At risk<br />
			(Prediabetes)<br />
			&gt;= 6.5 Diabetes<br />
			(As per ADA)</p>
			</td>
			<td>
			<p>QUANTITATIVE TURBIDIMETRIC IMMUNOASSAY</p>
			</td>
		</tr>
	</tbody>
</table>

<p><strong>Interpretation:</strong><br />
&nbsp;1 HbA1c testing provides an index of any blood glucose over past 2 to 4 months.</p>

<p>&nbsp;2 Approx 50% of HbA1c determined by previous 1 month and 75% during previous 2 months.</p>

<p>3 Hba1c levels are lowered by&nbsp;</p>

<p>(a) shortened RBC survival or lower mean RBC are eg. hemolysis,transfusions,recovery from acure blood loss.</p>

<p>(b) Hyperglycernia decreases erythrocyte survival on&nbsp;long term,so in poorly controlled patients may underestimate their mean plasma glucose concertration.</p>

<p>(c) Accuracy also effected by hemoglobinopathies&nbsp;HbSS,HbSC,HbCC) associated with high cell turnovers chronic&nbsp;alcohol or opiate&nbsp;use,iron deficency and lead&nbsp;</p>

<p>&nbsp; &nbsp; Poisoning and advanced chronic kidney disease.</p>

<p>ADA criteria&nbsp;between HbA1c and plasma glucose levels.&nbsp; &nbsp;</p>

<table border="1" cellpadding="1" cellspacing="1" style="height:5px; width:500px">
	<tbody>
		<tr>
			<td>Hemoglobin a/c(%)&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</td>
			<td>
			<p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;Approximate mean plasma</p>

			<p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Glucose (mg/dl)</p>
			</td>
		</tr>
	</tbody>
</table>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td>5</td>
			<td>97</td>
		</tr>
		<tr>
			<td>6</td>
			<td>126</td>
		</tr>
		<tr>
			<td>7</td>
			<td>154</td>
		</tr>
		<tr>
			<td>8</td>
			<td>183</td>
		</tr>
		<tr>
			<td>9</td>
			<td>212</td>
		</tr>
		<tr>
			<td>10</td>
			<td>240</td>
		</tr>
		<tr>
			<td>11</td>
			<td>269</td>
		</tr>
		<tr>
			<td>12</td>
			<td>298</td>
		</tr>
	</tbody>
</table>

<p>Uncertainity in accuracy in children.&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</p>

<p>&nbsp; &nbsp;&nbsp;</p>

<p>&nbsp;</p>
', '1', '1', '1', '337'),
('11', 'Semen Analaysis', NULL, '<p><strong>PHYSICAL CHARACTERISTICS</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>Volume</td>
			<td>{vol}</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Colour</td>
			<td>{sem_color}</td>
			<td>PEARLY WHITE</td>
		</tr>
		<tr>
			<td>Reaction</td>
			<td>{semen_reaction}</td>
			<td>ALKALINE</td>
		</tr>
		<tr>
			<td>Viscosity</td>
			<td>{Viscosity}</td>
			<td>VISCOUS</td>
		</tr>
		<tr>
			<td>Liquification Time</td>
			<td>{LIQTIME} min</td>
			<td>&lt; 30 MIN</td>
		</tr>
	</tbody>
</table>

<p><strong>MICROSCOPIC FEATURES</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>Total Sperm Count</td>
			<td>{TOTSPCOUNT} millions /ml</td>
			<td>15-200</td>
		</tr>
		<tr>
			<td>Active</td>
			<td>{semen_active} %</td>
			<td>&gt;60 %</td>
		</tr>
		<tr>
			<td>Sluggish</td>
			<td>{SLUGGISH} %</td>
			<td>&lt;30 %</td>
		</tr>
		<tr>
			<td>Non Motile</td>
			<td>{NONMOTILE} %</td>
			<td>&lt;20 %</td>
		</tr>
		<tr>
			<td>Abnormal Forms</td>
			<td>{Abnormal} %</td>
			<td>&lt;30 %</td>
		</tr>
		<tr>
			<td>Pus Cells</td>
			<td>{PUSCELL} /HPF</td>
			<td>NIL</td>
		</tr>
		<tr>
			<td>Epithelial cells</td>
			<td>{EPITH} /HPF</td>
			<td>NIL</td>
		</tr>
		<tr>
			<td>RBCs</td>
			<td>{sRBC} /HPF</td>
			<td>NIL</td>
		</tr>
		<tr>
			<td>Remarks</td>
			<td>{SEMENREMARKS}</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '12', '1', '1', '243'),
('12', 'ADA-MTB', NULL, '<h3><strong>ADA-MTB</strong></h3>

<p>FOR THE DETERMINATION OF ADENOSINE DEAMINASE ACTIVITY IN&nbsp; SERUM, PLASMA &amp; BIOLOGICAL FLUIDS</p>

<p>{THEAD} :{THV}</p>

<p><strong>Reference Values :</strong></p>

<table align="left" border="1" cellpadding="5" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td style="text-align:left; vertical-align:top">Serum,Plasma,Pleural,<br />
			Pericadial &amp; Ascitic Fluids</td>
			<td style="text-align:left; vertical-align:top">Normal : &lt;30 U/L<br />
			Suspect : 30- 40 U/L<br />
			Strong Suspect : 41 - 60 U/L<br />
			Positive : &gt;60 u/l</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">CSF</td>
			<td style="text-align:left; vertical-align:top">Normal : &lt; 10 U/L<br />
			Positive : &gt; 10 U/L</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '5', '1', '1', '422'),
('13', 'Blood Glucose Random', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<thead>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>BLOOD GLUCOSE RANDOM<br />
			&nbsp;</td>
			<td>{AGR1}</td>
			<td>mg/dL</td>
			<td>70.00-140.00</td>
		</tr>
	</tbody>
</table>

<p>Note</p>

<ol>
	<li>The diagnosis of Diabetes requires a fasting plasma glucose of &gt; or = 126 mg/dL and/or a random / 2 hr post glucose value of &gt; or = 200 mg/dL on at least 2 occasions.</li>
	<li>Very low glucose levels cause severe CNS dysfunction.</li>
	<li>Very high glucose levels (&gt;450 mg/dL in adults) may result in Diabetic Ketoacidosis &amp; is considered critical.</li>
</ol>

<p>&nbsp;</p>

<p>&nbsp;</p>
', '1', '1', '1', '397'),
('14', 'Prothrombin Time With INR', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>Patients Prothombin Time Value</td>
			<td>&nbsp;{PPTV} sec.</td>
		</tr>
		<tr>
			<td>Control Prothombin Time Value<br />
			(<em>Normal Range is 11 to 16sec.</em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
			<td>&nbsp;{CPTV} sec.</td>
		</tr>
		<tr>
			<td>Patient INR Value</td>
			<td>&nbsp;{PINRV}</td>
		</tr>
		<tr>
			<td>Oral Anticoauglant Therapeutic Range</td>
			<td>&nbsp;{OATR}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '255'),
('15', 'APTTY / PTTK', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf"><strong>Test Name</strong></td>
			<td style="background-color:#bfbfbf; text-align:center"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf"><strong>Unit</strong></td>
			<td style="background-color:#bfbfbf"><strong>Bio.Ref. Interval</strong></td>
			<td style="background-color:#bfbfbf"><strong>Method</strong></td>
		</tr>
		<tr>
			<td>Prothrombin Time (PT) *<br />
			Sample:Plasma</td>
			<td>{PT}</td>
			<td>secs</td>
			<td>11 - 16</td>
			<td>MACHANICAL CLOT&nbsp;DETECTION</td>
		</tr>
	</tbody>
</table>

<p>Interpretation:<br />
The test is used for the determination of the blood clotting factors II, V, VII and X (factors assays), for monitoring oral anticoagulant therapy, for diagnosis of acquired or inherited bleeding disorders.</p>

<p>PTT (PARTIAL THROMBOPLASTIN TIME), ACTIVATED / APTT * , Plasma</p>

<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf"><strong>Test Name</strong></td>
			<td style="background-color:#bfbfbf; text-align:center"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf"><strong>Unit</strong></td>
			<td style="background-color:#bfbfbf"><strong>Bio.Ref. Interval</strong></td>
			<td style="background-color:#bfbfbf"><strong>Method</strong></td>
		</tr>
		<tr>
			<td>&nbsp;PTT (Partial Thromboplastin Time),Activated / APTT</td>
			<td>{PAPT}</td>
			<td>secs</td>
			<td>30 - 40</td>
			<td>MACHANICAL CLOT&nbsp;DETECTION</td>
		</tr>
		<tr>
			<td>Control Value</td>
			<td>{CAPT}</td>
			<td>secs</td>
			<td>30</td>
			<td>MACHANICAL CLOT&nbsp;DETECTION</td>
		</tr>
	</tbody>
</table>

<p>Interpretation:<br />
A P T T values within 5 seconds of control time should be considered normal, probably abnormal if results are 10-20 sec longer than control and a definite abnormality with result &gt; 20 sec longer than control</p>
', '6', '1', '1', '411'),
('16', 'Glucose Fasting*,Plasma', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td style="text-align:center"><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Glucose Fasting*</strong><br />
			Sample:Plasma</td>
			<td style="text-align:center">{BGF}</td>
			<td>mg/dl</td>
			<td>&lt; 99 Normal<br />
			100-125 Near Normal<br />
			I.G.T)<br />
			&gt; 126 Diabetic&nbsp;</td>
			<td>GOD POD</td>
		</tr>
	</tbody>
</table>


', '1', '1', '1', '396'),
('17', 'Glucose (PP)*,Plasma', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Glucose PP*</strong><br />
			Sample:Plasma&nbsp;after meal</td>
			<td style="text-align:center">{BGPP}</td>
			<td>mg/dl</td>
			<td>80-140</td>
			<td>GOD POD</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '398'),
('18', 'C.S.F. Aspiration', NULL, '<h3><strong>Physical Examination</strong></h3>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
			<td><strong>Normal Value</strong></td>
		</tr>
		<tr>
			<td><strong>Appearance</strong></td>
			<td>{APP}</td>
			<td><em>CLEAR</em></td>
		</tr>
		<tr>
			<td><strong>Colour</strong></td>
			<td>{COLOUR}</td>
			<td><em>STRAW</em></td>
		</tr>
		<tr>
			<td><strong>Coagulum</strong></td>
			<td>{COALGU}</td>
			<td><em>ABSENT</em></td>
		</tr>
	</tbody>
</table>

<p><strong>Chemical Examination</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
			<td><strong>Normal Value</strong></td>
		</tr>
		<tr>
			<td><strong>Proteins</strong></td>
			<td>{PROTEINS}</td>
			<td>10 TO 40 mg/dl</td>
		</tr>
		<tr>
			<td><strong>Sugar</strong></td>
			<td>{SUGAR}</td>
			<td>40-70 mg/dl</td>
		</tr>
	</tbody>
</table>

<p><strong>Microscopic Examination</strong><br />
Total Cell Count&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {TCC} /cmm&nbsp;&nbsp;&nbsp;&nbsp; : <em>Range Between (0 to 9)</em></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td colspan="3"><strong>Differential Cell Count</strong></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Polymorphs</td>
			<td>{Polymorphs}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Lymphocytes</td>
			<td>{Lymphocyte}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Monocytes</td>
			<td>{Monocytes}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>R.B.Cs</td>
			<td>{RBCS}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Others</td>
			<td>{OTHER}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Gram Stain</td>
			<td>{GRAMSTAIN}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Stain For A.F.B</td>
			<td>{AFB}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '9', '1', '1', '386'),
('19', 'T3,T4,TSH', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:48px; width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Triiodothyronine [T3]</td>
			<td style="text-align:center; vertical-align:top">{T3}</td>
			<td style="text-align:left; vertical-align:top">ng/ml</td>
			<td style="text-align:left; vertical-align:top">0.00-1.51 ng/ml</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroxine [T4]</td>
			<td style="text-align:center; vertical-align:top">{T4}</td>
			<td style="text-align:left; vertical-align:top">ug/dl</td>
			<td style="text-align:left; vertical-align:top">4.66-9.32 ug/dl</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroid Stimulating Hormone (TSH)</td>
			<td style="text-align:center; vertical-align:top">{TSH}</td>
			<td style="text-align:left; vertical-align:top">ulU/mL</td>
			<td style="text-align:left; vertical-align:top">0.25-5.00 ulU/mL</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '13', '1', '1', '207'),
('20', 'Pleural Fluid Examination', NULL, '<p><strong>Physical Examination</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
			<td><strong>Normal Value</strong></td>
		</tr>
		<tr>
			<td><strong>Appearance</strong></td>
			<td>{APP}</td>
			<td><em>CLEAR</em></td>
		</tr>
		<tr>
			<td><strong>Colour</strong></td>
			<td>{COLOUR}</td>
			<td><em>STRAW</em></td>
		</tr>
		<tr>
			<td><strong>Coagulum</strong></td>
			<td>{COALGU}</td>
			<td><em>ABSENT</em></td>
		</tr>
	</tbody>
</table>

<p><strong>Chemical Examination</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
		</tr>
		<tr>
			<td><strong>Proteins</strong></td>
			<td>{PROTEINS}</td>
		</tr>
		<tr>
			<td><strong>Sugar</strong></td>
			<td>{SUGAR}</td>
		</tr>
	</tbody>
</table>

<p><strong>Microscopic Examination</strong><br />
Total Cell Count&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {TCC} /cmm&nbsp;&nbsp;&nbsp;&nbsp;</p>

<p>&nbsp;</p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td colspan="3"><strong>Differential Cell Count</strong></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Polymorphs</td>
			<td>{Polymorphs}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Lymphocytes</td>
			<td>{Lymphocyte}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Mesothelial</td>
			<td>{MESO}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>R.B.Cs</td>
			<td>{RBCS}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Gram Stain</td>
			<td>{GRAMSTAIN}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Stain For A.F.B</td>
			<td>{AFB}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '9', '1', '1', '258'),
('21', 'Bilirubin*,Serum', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf"><strong>Test Name</strong></td>
			<td style="background-color:#bfbfbf; text-align:center"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf"><strong>Unit</strong></td>
			<td style="background-color:#bfbfbf"><strong>Bio.Ref. Interval</strong></td>
			<td style="background-color:#bfbfbf"><strong>Method</strong></td>
		</tr>
		<tr>
			<td>
			<p>Bilirubin Total*<br />
			Sample:Serum</p>
			</td>
			<td style="text-align:center">
			<p>{BT}</p>
			</td>
			<td>
			<p>mg/dl</p>
			</td>
			<td>
			<p>0.20-2.00</p>
			</td>
			<td>
			<p>DIAZO</p>
			</td>
		</tr>
		<tr>
			<td>
			<p>Bilirubin Direct*<br />
			Sample:Serum</p>
			</td>
			<td style="text-align:center">
			<p>{BD}</p>
			</td>
			<td>
			<p>mg/dl</p>
			</td>
			<td>
			<p>0.7-1.3</p>
			</td>
			<td>
			<p>DIAZO</p>
			</td>
		</tr>
		<tr>
			<td>
			<p>Bilirubin Indirect*<br />
			Sample:Serum</p>
			</td>
			<td style="text-align:center">
			<p>{BI}</p>
			</td>
			<td>
			<p>mg/dl</p>
			</td>
			<td>
			<p>0.00-0.70</p>
			</td>
			<td>
			<p>DIAZO</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '404'),
('22', 'Troponin T (Trop T)', NULL, '<h3>Troponin&nbsp;&nbsp; : {TROP}</h3>
', '12', '1', '1', '190'),
('23', 'Troponine I (Trop I)', NULL, '<h3>Troponine I (Trop I)&nbsp; : {TROPI}</h3>
', '12', '1', '1', '191'),
('24', 'SERUM PROTEIN', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SERUM PROTEIN</td>
			<td>{SPro}</td>
			<td>mg%</td>
			<td>6-8mg%</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '224'),
('25', 'SERUM BILIRUBIN [TOTAL]', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>Test Name</td>
			<td>Observed Values</td>
			<td>Unit</td>
			<td>Normal Values&nbsp;</td>
		</tr>
		<tr>
			<td><em>SERUM BILIRUBIN TOTAL</em></td>
			<td>{SERBIL}</td>
			<td>mg%</td>
			<td>0.20-2.00 mg%</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '240'),
('26', 'SGOT / Aspartate Aminotransferase (AST)*,Serum                     ', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test &nbsp;Name&nbsp;</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref.Interval</strong></td>
			<td>&nbsp;Method</td>
		</tr>
		<tr>
			<td>SGOT / Aspartate Aminotransferase (AST)*<br />
			Sample:Serum</td>
			<td>{SGOT}</td>
			<td>U/L</td>
			<td>&lt; 35</td>
			<td>IFCC</td>
		</tr>
	</tbody>
</table>

', '1', '1', '1', '245'),
('27', 'SGPT / Alanine Aminotransferase (ALT)*,Serum', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test &nbsp;Name&nbsp;</strong></td>
			<td>Result</td>
			<td>Unit</td>
			<td><strong>Bio.Ref.Interval&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>
			<td>Method</td>
		</tr>
		<tr>
			<td>SGPT / Alanine Aminotransferase (ALT)*<br />
			Sample:Serum</td>
			<td>{SGPT}</td>
			<td>U/L</td>
			<td>&lt; 40&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</td>
			<td>IFCC</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '244'),
('28', 'T.L.C.', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>TLC</td>
			<td>{TLC1}</td>
			<td>/cu mm</td>
			<td>4000.00-11000.00 /cu mm</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '209'),
('29', 'D.L.C.', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td><em>D.L.C.</em></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><em>NEUTROPHILS</em></td>
			<td>{NEU}</td>
			<td>%</td>
			<td>45.00-70.00 %</td>
		</tr>
		<tr>
			<td><em>LYMPHOCYTES</em></td>
			<td>{LYMP}</td>
			<td>%</td>
			<td>20.00-45.00 %</td>
		</tr>
		<tr>
			<td><em>EOSINOPHILS</em></td>
			<td>{EOSIN}</td>
			<td>%</td>
			<td>1.00-8.00 %</td>
		</tr>
		<tr>
			<td><em>MONOCYTES</em></td>
			<td>{MONO}</td>
			<td>%</td>
			<td>1.00-6.00 %</td>
		</tr>
		<tr>
			<td>BASOPHILES</td>
			<td>{BAND}</td>
			<td>%</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '6', '1', '1', '367'),
('30', 'NEUTROPHILS', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>NEUTROPHILS</td>
			<td>{NEU}</td>
			<td>%</td>
			<td>45.00-70.00&nbsp;U/L</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '425'),
('31', 'LYMPHOCYTES', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>LYMPHOCYTES</td>
			<td>{LYMP}</td>
			<td>%</td>
			<td>20.00-45.00 %</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '426'),
('32', 'Absolute Eosinophil Count', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf"><strong>Test Name</strong></td>
			<td style="background-color:#bfbfbf; text-align:center"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf"><strong>Unit</strong></td>
			<td style="background-color:#bfbfbf"><strong>Bio.Ref. Interval</strong></td>
			<td style="background-color:#bfbfbf"><strong>Method</strong></td>
		</tr>
		<tr>
			<td>Absolute Eosinophil Count</td>
			<td>{AEOSIN}</td>
			<td>per cu mm</td>
			<td>40-440</td>
			<td>MICROSCOPIC EXAMINATION</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '427'),
('33', 'RBC', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>RBC</td>
			<td>{RBC}</td>
			<td>million/cm</td>
			<td>3.50-5.00</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '250'),
('34', 'PCV', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>PCV</td>
			<td>{PCV}</td>
			<td>%</td>
			<td>34.00-47.00</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '261'),
('35', 'MCV', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>MCV</td>
			<td>{MCV}</td>
			<td>pg</td>
			<td>25.00-33.00</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '273'),
('36', 'MCH', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SGOT/ALT</td>
			<td>{SGPT}</td>
			<td>U/L</td>
			<td>10.00-45.00 U/L</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '275'),
('37', 'MCHC', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>MCHC</td>
			<td>{MCHC}</td>
			<td>g/dl</td>
			<td>28.00-36.00</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '274'),
('38', 'PLATELET COUNT', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>PLATELET COUNT</td>
			<td>{PLATELET}</td>
			<td>LAKH PER CU MM</td>
			<td>150000-450000</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '259'),
('39', 'Blood Urea*,Serum', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>&nbsp;Bio.Ref.Interval&nbsp;</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Blood Urea*</strong><br />
			Sample: Serum</td>
			<td style="text-align:center">{BU}</td>
			<td>mg/dL&nbsp;</td>
			<td>15-45&nbsp;</td>
			<td>GLDH</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '393'),
('40', 'Creatinine*,Serum', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref.Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Creatinine*</strong><br />
			Samle:Serum</td>
			<td>{SC}</td>
			<td>mg/dl</td>
			<td>0.7-1.3</td>
			<td>ALKALNIE PICRATE</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '237'),
('41', ' Uric Acid,*Serum', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref. Interval</strong></td>
			<td>Method</td>
		</tr>
		<tr>
			<td>Uric Acid*<br />
			Sample:Serum</td>
			<td>{SUA}</td>
			<td>mg/dl&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td>2.6-7.2</td>
			<td>URICASE</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '221'),
('42', 'SERUM SODIUM', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SERUM SODIUM</td>
			<td>{SS}</td>
			<td>mmpl/L</td>
			<td>135.00-148.00</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '223'),
('43', 'Potassium*,Serum', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td>Bio.Ref.Interval</td>
			<td>Method</td>
		</tr>
		<tr>
			<td>Potassium*<br />
			Sample:Serum</td>
			<td>{SP}</td>
			<td>m Mol /L</td>
			<td>3.8-5.4</td>
			<td>ISE</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '226'),
('44', 'Calcium*,Serum', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Calcium*</strong><br />
			Sample:Serum</td>
			<td>{SCA}</td>
			<td>mg/dl</td>
			<td>8.8-10.2&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td>ARSENAZO III</td>
		</tr>
	</tbody>
</table>

<p>(*) Test not done under NABL accredited Scope, (**) Test Performed at Apollo Diagnostics &amp; Path Lab.</p>

<p>This report is not for medico legal purpose. If clinical correlation is not established, kindly repeat the test at no additional cost within seven days.&nbsp;</p>

<p>&nbsp;</p>
', '1', '1', '1', '239'),
('45', 'SERUM ALBUMIN', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SERUM ALBUMIN</td>
			<td>{SA}</td>
			<td>mg/dl</td>
			<td>3.50-5.50</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '421'),
('46', 'SERUM GLOBULIN', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SERUM GLOBULIN</td>
			<td>{SG}</td>
			<td>mg/dl</td>
			<td>2.00-3.50</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '344'),
('47', 'ESR*,Blood', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref.Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>ESR*,</strong>Blood</td>
			<td>{ESR1}</td>
			<td>mm in 1st hr.</td>
			<td>&lt; 9</td>
		</tr>
	</tbody>
</table>

<p>(*) Test not done under NABL accredited Scope, (**) Test Performed at Apollo Diagnostics &amp; Path Lab.</p>

<p>This report is not for medico legal purpose. If clinical correlation is not established, kindly repeat the test at no additional cost within seven days.&nbsp;</p>

<p>&nbsp;</p>
', '6', '1', '1', '358'),
('48', 'S. ALKALINE PHOSPHATASE', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>S. ALKALINE PHOSPHATASE</td>
			<td>{SALKALINE}</td>
			<td>U/L</td>
			<td>50.00-128.00</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '417'),
('50', 'Gram Stain', NULL, '<table border="0" cellpadding="0" cellspacing="0" xss=removed>
 <tbody>
  <tr>
   <td><strong>Test Name</strong></td>
   <td><strong>Observed Values</strong></td>
   <td><strong>Unit</strong></td>
   <td><strong>Normal Values</strong></td>
  </tr>
  <tr>
   <td>Gram Stain</td>
   <td>{GRAMSTAIN}</td>
   <td> </td>
   <td> </td>
  </tr>
 </tbody>
</table>
', '4', '1', '1', '3417'),
('51', 'T3 (TRIIODOTHYROININE)', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>T3</td>
			<td>{T3}</td>
			<td>ng/ml</td>
			<td>0.60-1.51</td>
		</tr>
	</tbody>
</table>
', '13', '1', '1', '208'),
('52', 'T4 (THYROXINE)', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>T4</td>
			<td>{T4}</td>
			<td>ug/dl</td>
			<td>4.66-9.32</td>
		</tr>
	</tbody>
</table>
', '13', '1', '1', '206'),
('54', 'Hemoglobin*', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Haemoglobin*</strong><br />
			Sample:Blood</td>
			<td>{HB}</td>
			<td>g/dl</td>
			<td>13.5 - 17.5</td>
			<td>
			<p>PHOTOMETRIC</p>
			</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '333'),
('55', 'URINE EXAMINATION, ROUTINE * ,Urine', NULL, '<p>&nbsp;(Automated Strip Test, Microscopy)</p>

<table border="0" cellpadding="1" cellspacing="1" style="width:800px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref.Interval</strong></td>
		</tr>
		<tr>
			<td><strong>Physical Examination</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Colour</td>
			<td>{urine_colo}</td>
			<td>&nbsp;</td>
			<td>Pale Yellow</td>
		</tr>
		<tr>
			<td>Appearance</td>
			<td>{urine_tran}</td>
			<td>&nbsp;</td>
			<td>Clear</td>
		</tr>
		<tr>
			<td>Specific Gravity</td>
			<td>{usg}</td>
			<td>&nbsp;</td>
			<td>1.003-1.035</td>
		</tr>
		<tr>
			<td>pH</td>
			<td>{urine_ph}</td>
			<td>&nbsp;</td>
			<td>4.7-7.5</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><strong>Chemical Examination</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Protein</td>
			<td>{urine_albu}</td>
			<td>mg%</td>
			<td>NIL</td>
		</tr>
		<tr>
			<td>Glucose</td>
			<td>{urine_suga}</td>
			<td>gms%</td>
			<td>NIL</td>
		</tr>
		<tr>
			<td>
			<p>&nbsp;</p>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><strong>Microscopic Examination</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Pus Cells</td>
			<td>{upuscells}</td>
			<td>/hpf</td>
			<td>0-5</td>
		</tr>
		<tr>
			<td>Epithial Cells</td>
			<td>{uepithial}</td>
			<td>/hpf</td>
			<td>0-5</td>
		</tr>
		<tr>
			<td>RBCs</td>
			<td>{urbc}</td>
			<td>/hpf</td>
			<td>0-3</td>
		</tr>
		<tr>
			<td>Casts</td>
			<td>{urinecasts}</td>
			<td>/hpf</td>
			<td>Not Detected</td>
		</tr>
		<tr>
			<td>Crystals</td>
			<td>{urinecryst}</td>
			<td>/hpf</td>
			<td>Not Detected</td>
		</tr>
		<tr>
			<td>Bacteria flora few</td>
			<td>{BFF}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '3', '1', '1', '174'),
('56', 'LIPID PROFILE', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey"><strong>Result</strong></td>
			<td style="background-color:lightgrey"><strong>Bio. Ref.Interval</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">Cholesterol(Total)</td>
			<td style="vertical-align:top">{l_Choleste}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt;200 Desirable<br />
			200-239 Borderline High<br />
			&gt; 240 High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">Triglyceride</td>
			<td style="vertical-align:top">{l_Triglyce}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 150 Normal<br />
			150-199 Borderline High<br />
			200-499 High<br />
			&gt;500 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">HDL&nbsp;Cholesterol ( Good Cholesterol )</td>
			<td style="vertical-align:top">{l_hdl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">30-70</td>
		</tr>
		<tr>
			<td style="vertical-align:top">LDL&nbsp;Cholesterol ( Bad Cholesterol ) ( Direct )</td>
			<td style="vertical-align:top">{l_ldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 100 Optimal<br />
			100-129 Nr.<br />
			Optimal/Above Optimal<br />
			130-159 Borderline High<br />
			160-189 High<br />
			&gt; 190 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">VLDL</td>
			<td style="vertical-align:top">{l_vldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">10-33</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '282'),
('57', 'BT - CT', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>BLEEDING TIME</td>
			<td>{blood_bt}</td>
			<td>min.</td>
			<td>upto 7 min</td>
		</tr>
		<tr>
			<td>CLOTTING TIME</td>
			<td>{blood_ct}</td>
			<td>min.</td>
			<td>4.00 - 12.00 min.</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '6', '1', '1', '388'),
('58', 'Anti - HCV', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top">Test Name</td>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top">Result</td>
			<td style="background-color:lightgrey; text-align:left; vertical-align:top">Method</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Anti - &nbsp;HCV*<br />
			(Hepatitis &nbsp;C &nbsp; Virus)<br />
			Sample: Serum</td>
			<td style="text-align:left; vertical-align:top">{hcv}</td>
			<td style="text-align:left; vertical-align:top">IMMUNOCHROMATOGRAPHY</td>
		</tr>
	</tbody>
</table>

<p>Note: &nbsp;The hepatitis &nbsp;C virus HCV is now the cause of 90% post transfusion hepatitis it is also found in drug addicts and also contributes to sporadic acute viral hepatitis &ndash;HCV is a RNA &nbsp;flavi virus and the incubation period may be short (1-4 weeks ) or long ( 6-12 weeks . chronicity of infection is reported in &gt; 10 % .The frequency of post transfusion hepatitis can be definitely reduced with help of serological assays available for HCV.</p>
', '12', '1', '1', '335'),
('59', 'TSH', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:48px; width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroid Stimulating Hormone (TSH)</td>
			<td style="text-align:center; vertical-align:top">{TSH}</td>
			<td style="text-align:left; vertical-align:top">ulU/mL</td>
			<td style="text-align:left; vertical-align:top">0.25-5.00 ulU/mL</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '13', '1', '1', '189'),
('60', 'GENERAL BLOOD PICTURE(GBP)', NULL, '<p>RBC-  shows moderate degree of anisopoikilocytosis. Dimorphic population of rbc&#39;s are present comprising of both microcytic hypochromic as well as normocytic normochromic cells along with some tear drop cells, pencil cells and few schistiocytes. </p>

<p>WBC-  TLC is within normal range and DLC shows normal distribution of cells.</p>

<p>PLATELETS- are adequate with normal morphology.</p>

<p>No Hemoparasite seen</p>

<p><strong><u>IMPRESSION-</u> DIMORPHIC ANEMIA WITH PREDOMINANCE OF MICROCYTIC HYPOCHROMIC CELLS.</strong></p>

<p> </p>
', '6', '1', '1', '345'),
('61', 'PAP SMEAR', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:600px">
	<tbody>
		<tr>
			<td colspan="2" rowspan="1" style="text-align:left; vertical-align:top">Speciman</td>
			<td style="text-align:left; vertical-align:top; width:10px">:</td>
			<td style="text-align:left; vertical-align:top; width:400px">Cervical / vaginal cytology</td>
		</tr>
		<tr>
			<td colspan="2" rowspan="1" style="text-align:left; vertical-align:top">Clinical History</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Post menopausal</td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" rowspan="1" style="text-align:left; vertical-align:top">Microscopy</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">By Bethesda system</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Adequacy</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Adequate</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Microscopy</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Smears shows superficial, intermediate squamous cells &amp; parabasal cells with many polymorph<br />
			<br />
			No trichomonas or fungal organism seen</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Endocervical Cells</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Present</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Koilocytotic Cells</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Absent</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Dysplastic Cells</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Absent</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">Malignant Cells</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Absent</td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:left; vertical-align:top; white-space:nowrap; width:200px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:left; vertical-align:top; white-space:nowrap; width:200px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" rowspan="1" style="text-align:left; vertical-align:top; white-space:nowrap; width:200px">Impression</td>
			<td style="text-align:left; vertical-align:top">:</td>
			<td style="text-align:left; vertical-align:top">Negative for Intraepithelial lesion and malignancy</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top; width:50px">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
			<td style="text-align:left; vertical-align:top">&nbsp;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '10', '1', '1', '262'),
('62', 'Ra Factor', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Value</strong></td>
			<td><strong>Unit</strong></td>
		</tr>
		<tr>
			<td>R A Factor</td>
			<td>Negative</td>
			<td>IU/ml</td>
		</tr>
	</tbody>
</table>

<p>INTERPRETATION:</p>

<p>&lt;20&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; IU/ml&nbsp;&nbsp;&nbsp; =&nbsp; negative<br />
21-25&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; IU/ml&nbsp;&nbsp;&nbsp; =&nbsp; borderline<br />
&gt;25&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; IU/ml&nbsp;&nbsp;&nbsp; =&nbsp; positive</p>

<p>COMMENTS</p>

<p>Owing to the nature of RF, an all purpose cut-off value between a positive<br />
&amp; negative result is considered impractical</p>

<p>Highly elevated values are found mainly in rheumatoid arthritis, whereas nonspecific positive generally give low values.</p>
', '1', '1', '1', '251'),
('63', 'Widal Test', NULL, '<p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; WIDAL TEST</strong></p>

<p>Slide agglutination test for Salmonella group of organisms reveal following titers....</p>

<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>
			<p><strong>Dilution</strong></p>
			</td>
			<td>
			<p><strong>1/20</strong></p>
			</td>
			<td>
			<p><strong>1/40</strong></p>
			</td>
			<td>
			<p><strong>1/80</strong></p>
			</td>
			<td>
			<p><strong>1/160</strong></p>
			</td>
			<td>
			<p><strong>1/320</strong></p>
			</td>
		</tr>
		<tr>
			<td>
			<p>S TYPHI &quot; O &quot;</p>
			</td>
			<td>
			<p>{STyphiO1}</p>
			</td>
			<td>
			<p>{STyphiO2}</p>
			</td>
			<td>
			<p>{STyphiO3}</p>
			</td>
			<td>
			<p>{STyphiO4}</p>
			</td>
			<td>
			<p>{STyphiO5}</p>
			</td>
		</tr>
		<tr>
			<td>
			<p>S TYPHI &quot; H &quot;</p>
			</td>
			<td>
			<p>{STyphiH1}</p>
			</td>
			<td>
			<p>{STyphiH2}</p>
			</td>
			<td>
			<p>{STyphiH3}</p>
			</td>
			<td>
			<p>{STyphiH4}</p>
			</td>
			<td>
			<p>{STyphiH5}</p>
			</td>
		</tr>
		<tr>
			<td>
			<p>S PARATYPHI&nbsp; &quot; AH &quot;</p>
			</td>
			<td>
			<p>{SParaTyphiAH1}</p>
			</td>
			<td>
			<p>{SParaTyphiAH2}</p>
			</td>
			<td>
			<p>{SParaTyphiAH3}</p>
			</td>
			<td>
			<p>{SParaTyphiAH4}</p>
			</td>
			<td>
			<p>{SParaTyphiAH5}</p>
			</td>
		</tr>
		<tr>
			<td>
			<p>S PARATYPHI&nbsp; &quot; BH &quot;</p>
			</td>
			<td>
			<p>{SParaTyphiBH1}</p>
			</td>
			<td>
			<p>{SParaTyphiBH2}</p>
			</td>
			<td>
			<p>{SParaTyphiBH3}</p>
			</td>
			<td>
			<p>{SParaTyphiBH4}</p>
			</td>
			<td>
			<p>{SParaTyphiBH5}</p>
			</td>
		</tr>
	</tbody>
</table>

<p>COMMENT:&nbsp; &nbsp; WIDAL TEST IS &ldquo; NEGATIVE &rdquo;&nbsp;&nbsp; &nbsp;<br />
&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
NOTE : Titers of agglutinins to Salmonella antigens , somatic (0) in values upto 40 and for Flagellar (H) in values up to 80 are the endemic titers. Agglutinins may also rise in the serum following Typhoid immunization, past Salmonella &nbsp;infections or infections due to Enterobacteriaceae sharing common antigens. Agglutinins on the other hand may not rise in patients receiving effective antibiotic therapy early in the course of Salmonellosis.<br />
Diagnostic value of Widal test is increased if rising titers are demonstrated in serial samples. &nbsp;&nbsp;</p>
', '12', '1', '1', '160'),
('64', 'Serum Amylase', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <strong>&nbsp;Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Serum Amylase</td>
			<td style="text-align:center">{AMYLASE}</td>
			<td>IU/L</td>
			<td>20.00-80.00 IU/L</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '241'),
('65', 'Serum Lipase', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Serum Lipase</td>
			<td style="text-align:center">{LIPASE}</td>
			<td>U/L</td>
			<td>0-60 U/L</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '229'),
('66', 'Serum Cholesterol', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Serum Cholesterol</td>
			<td style="text-align:center">{Cholestero}</td>
			<td>mg/dl</td>
			<td>130-200 mg/dl</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '238'),
('67', 'Serum Trigycerides', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Serum Trigycerides</td>
			<td style="text-align:center">{Trigycerides}</td>
			<td>mg/dl</td>
			<td>45-160mg/dl</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '222'),
('68', 'Blood Group RH', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:500px">
	<thead>
		<tr>
			<th scope="col" style="text-align: left;">Blood Group</th>
			<th scope="col" style="text-align: left;">Rh (&nbsp;Anti - D)</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>{ABO}</td>
			<td>{RHF}</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '395'),
('69', 'URINE PREGNANCY', NULL, '<p>URINE FOR PREGNANCY&nbsp; :&nbsp; {PREGNANCYT}</p>
', '8', '1', '1', '178'),
('70', 'STOOL ROUTINE EXAMINATION', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></td>
			<td><strong>Observed Values</strong></td>
		</tr>
		<tr>
			<td><em><strong>Physical Examination</strong></em></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Colour</td>
			<td>&nbsp;</td>
			<td>{stool_colo}</td>
		</tr>
		<tr>
			<td>Consistency</td>
			<td>&nbsp;</td>
			<td>{Consistenc}</td>
		</tr>
		<tr>
			<td>Mucus</td>
			<td>&nbsp;</td>
			<td>{Mucus}</td>
		</tr>
		<tr>
			<td>PH</td>
			<td>&nbsp;</td>
			<td>{SPH}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><em><strong>Chemical Examination</strong></em></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Reducing Sugar</td>
			<td>&nbsp;</td>
			<td>{RSugar}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><em><strong>Microscopic Examination</strong></em></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Pus Cells</td>
			<td>&nbsp;</td>
			<td>{PCells}</td>
		</tr>
		<tr>
			<td>RBCs</td>
			<td>&nbsp;</td>
			<td>{sRBC}</td>
		</tr>
		<tr>
			<td>Ova</td>
			<td>&nbsp;</td>
			<td>{Ova}</td>
		</tr>
		<tr>
			<td>Cyst</td>
			<td>&nbsp;</td>
			<td>{Cyst}</td>
		</tr>
		<tr>
			<td>Fat Globules</td>
			<td>&nbsp;</td>
			<td>{FatGlobuli}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '3', '1', '1', '213'),
('71', 'TYPHOID IgG &  IgM*,Serum', NULL, '<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>Test &nbsp;Name</td>
			<td>Result</td>
			<td>Unit&nbsp;</td>
			<td>Bio.Ref.Interval&nbsp;</td>
			<td>Method</td>
		</tr>
		<tr>
			<td>TYPHOID, IgG*<br />
			Sample:Serum</td>
			<td>{IgG}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>IMMUNOCHROMATOGRAPHY</td>
		</tr>
		<tr>
			<td>TYPHOID, IgM*<br />
			Sample:Serum</td>
			<td>{IgM}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>IMMUNOCHROMATOGRAPHY</td>
		</tr>
	</tbody>
</table>

<p>TEST RESULTS&nbsp; &nbsp; &nbsp; : RESULTS &amp; CLINICAL INTERPRETATION &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
IgM Positive only&nbsp; &nbsp; &nbsp; : Acute Typhoid Fever&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
IgG &amp; IgM Positive&nbsp; &nbsp;: Acute Typhoid fever(in the middle stage of infection)&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
IgG Positive only&nbsp; &nbsp; &nbsp; : Relapse or reinfection or previous infection(Current fever may not be due to Typhoid) &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
IgM &amp; IgG Negative&nbsp; : No Typhoid fever &nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;<br />
This test is highly specific in detecting typhoid fever.The 95% sensitivity of the test is much more superior to either Culture or Widal test. Very high positive predictivity value and negative predictivity value of TYPHOID IgG &amp; IgM is also far more superior to both Culture and Widal test. Dot EIA for typhoid fever is the first known qualitative antibody detection test against a specific antigen of Salmonella Typhi,the causative agent for typhoid fever. The test detects both IgG and IgM antibodies separately and simultaneously to indicate the status of acute infection or previous exposure and takes only 1 Hr to complete as compare to longer period in case of both culture and Widal Test.&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;</p>

<p>&nbsp;
<p>&nbsp;&nbsp;</p>
</p>
', '12', '1', '1', '188'),
('72', 'MALARIA ANTIGEN', NULL, '<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>Test &nbsp;Name</td>
			<td>Result&nbsp;</td>
			<td>Unit&nbsp;</td>
			<td>Bio.Ref.Interval</td>
			<td>Method</td>
		</tr>
		<tr>
			<td>Malarial Antigen<br />
			P.Vivax *<br />
			Sample:Blood</td>
			<td>{MALARIA}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>IMMUNOCHROMATOGRAPHY</td>
		</tr>
		<tr>
			<td>Malarial Antigen<br />
			Falciparum*<br />
			Sample:Blood</td>
			<td>{MALARIA}</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>IMMUNOCHROMATOGRAPHY</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (*) Test not done under NABL accredited Scope<br />
&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;</p>
', '6', '1', '1', '278'),
('73', 'GAMA GT', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:48px; width:585px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">GAMMA GT</td>
			<td style="text-align:center; vertical-align:top">{GAMAGT}</td>
			<td style="text-align:left; vertical-align:top">IU/L</td>
			<td style="text-align:left; vertical-align:top"><em>9-52IU/L</em></td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '346'),
('74', 'CPK-NAC', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:48px; width:585px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">CPK-NAC</td>
			<td style="text-align:center; vertical-align:top">{CPNACK}</td>
			<td style="text-align:left; vertical-align:top">IU/L</td>
			<td style="text-align:left; vertical-align:top"><em>35-174IU/L</em></td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '374'),
('75', 'CPK-MB', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:48px; width:585px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">CPK-MB</td>
			<td style="text-align:center; vertical-align:top">{CPKMB}</td>
			<td style="text-align:left; vertical-align:top">IU/L</td>
			<td style="text-align:left; vertical-align:top"><em>&lt;24 IU/L</em></td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '375'),
('76', 'PSA(PROSTATIC SPECIFIC ANTIGEN)', NULL, '<p>PSA : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {PSA} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ng/ml</p>

<table style="height:183px; width:701px">
	<tbody>
		<tr>
			<td>
			<p>NORMAL RANGE:&nbsp;&nbsp; &lt;40Yrs - 0.21-1.72&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 40-49 Yrs - 0.27-2.19</p>

			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; 50-59 Yrs - 0.27-3.42&nbsp;&nbsp;&nbsp;&nbsp; 60-69 Yrs - 0.22-6.17</p>

			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &gt;60 Yrs - 0.21-6.77</p>

			<p>SERUM TESTING FOR PSA IS VERY IMPORTANT TOOL TO SCREEN FOR PROSTATE CANCER AND TO MONITOR THERAPY OF THIS DISEASE. PSA IS PROSTATE-SPECIFIC BUT NOT CANCER-SPECIFIC AND APART FROM PROSTATE CANCER INCREASED LEVELS MAY BE FOUND IN BENIGN PROSTATE HYPERTROPHY, PROSTATITIS, INCREASING AGE, ACUTE RETENTION OF URINE, INFECTION AND PROSTATE BIOPSY.</p>

			<p>PSA IS RARELY RAISED IN HEALTY MEN AND IS ABSENT IN NORMAL WOMEN. THERE IS NO PSA PRESENT IN ANYOTHER NORMAL TISSUE FROM MEN OR IN PATIENTS WITH OTHER CANCERS OF THE BREAST, LUNG, COLON, RECTUM, STOMACH PANCREAS OR THYROID.</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '254'),
('77', 'RETICULOCYTE COUNT', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Value</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Value</strong></td>
		</tr>
		<tr>
			<td>RETICULOCYTE COUNT</td>
			<td>{Reticount}</td>
			<td>%</td>
			<td>0.20 - 2.00 %</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '6', '1', '1', '249'),
('78', 'ASO Titer', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:48px; width:585px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">ASO</td>
			<td style="text-align:center; vertical-align:top">{ASO}</td>
			<td style="text-align:left; vertical-align:top">IU/mL</td>
			<td style="text-align:left; vertical-align:top"><em>&lt;200IU/mL</em></td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '409'),
('79', 'DENGUE', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="height:108px; width:551px">
	<tbody>
		<tr>
			<td><strong>TEST</strong></td>
			<td><strong>RESULT</strong></td>
		</tr>
		<tr>
			<td>Dengue Ns1</td>
			<td>{DNS1}</td>
		</tr>
		<tr>
			<td>Dengue IgM</td>
			<td>{DIGM}</td>
		</tr>
		<tr>
			<td>Dengue IgG</td>
			<td>{DIGG}</td>
		</tr>
	</tbody>
</table>

<p>Dengue viruses is a flavivirus found largely in areas of the tropic and sub-tropics. There are four distinct but antigeniclly related serotypes of dengue viruses, and transmission is by mosquito, principally Aedes aegypti and Aedes albopictus.</p>

<p>Primary infection with dengue virus results in a self-limitig disease characterized by mild to high fever lasting 3 to 7 days, sever headache with pain behind the eyes, muscle and joint pain, rash and vomiting. Secondary infection is the more common from of the disease in many parts of Southeast Asia and South America; IgM antibodies are not detectable until 5-10 days in case of primary dengueinfection and until 4-5 days in secondary infection after the onset of illness. IgG appear after 14 days and persist for life in case primary infection and rise within 1-2 days after the onset of symptoms in secondary infection.</p>

<p>Primary dengue virus infection is characterized by elevatons in specific NS1 antigen levels 0 to 9 days after the onset of symptoms; this generally presists upto 15 days. Earler diagnosis of Dengue reduces risk of compliation such as DHF or DSS, especially in countries where dengue is endemic.</p>
', '12', '1', '1', '366'),
('80', 'Direct Coombs Test', NULL, '<p>DCT&nbsp; :&nbsp; {DCT}</p>
', '12', '1', '1', '362'),
('81', 'Stool for Occult Blood', NULL, '<h3>Stool for Occult Blood&nbsp; :&nbsp; {occult}</h3>
', '3', '1', '1', '215'),
('82', 'Bile Salt', NULL, '', '8', '1', '1', '0'),
('83', 'BILE PIGMENT', NULL, '', '8', '1', '1', '0'),
('84', 'Hepatitics  A', NULL, '', '5', '1', '1', '0'),
('85', 'Hepatitics E', NULL, '', '5', '1', '1', '0'),
('86', 'V.D.R.L', NULL, '<h3>V.D.R.L&nbsp; &nbsp;:&nbsp; &nbsp;{V.D.R.L}</h3>
', '12', '1', '1', '168'),
('87', 'G6PD', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>G6PD</td>
			<td style="text-align:center">{G6PD}</td>
			<td>u/g HB</td>
			<td>6.40-18.5</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '2976'),
('88', 'MALIGNANT CELLS', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td>&nbsp;</td>
			<td><strong>Value</strong></td>
		</tr>
		<tr>
			<td>MALIGNANT CELLS</td>
			<td>&nbsp;</td>
			<td>{MC}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '9', '1', '1', '276'),
('89', 'Serum Electrolyte', NULL, '<p>&nbsp;</p>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Na+</td>
			<td style="text-align:center">{Na}</td>
			<td>mmol/L</td>
			<td>135.0-148.0</td>
		</tr>
		<tr>
			<td>K+</td>
			<td style="text-align:center">{K}</td>
			<td>mmol/L</td>
			<td>3.50-5.30</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '236'),
('90', '24 Hrs Urinary Albumin', NULL, '<p>Vol. of urine- 1000ml/24Hrs.</p>

<p>Specific Gravity-1010</p>

<p>Total Protein excreted &ndash; 200mg/24Hrs.</p>

<p><strong>Guideline for interpretation</strong></p>

<p>Reference Range</p>

<p>21.3-119.6mg/24Hrs.</p>

<p>0.02-0.11gm/24Hrs.</p>
', '3', '1', '1', '430'),
('91', 'urine for Bence Jones protein', NULL, '<p>Urine for Bence Jones protein: {BanP}</p>
', '1', '1', '1', '180'),
('92', 'Heamogram', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>HAEMOGLOBIN</td>
			<td style="text-align:center">{HB}</td>
			<td>gm%</td>
			<td>12.00-16.00 gm%</td>
		</tr>
		<tr>
			<td>T.L.C.</td>
			<td style="text-align:center">{TLC}</td>
			<td>per cu mm</td>
			<td>4000.00-11000.00 /cu mm</td>
		</tr>
		<tr>
			<td>D.L.C.</td>
			<td style="text-align:center">&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>NEUTROPHILS</td>
			<td style="text-align:center">{NEU}</td>
			<td>%</td>
			<td>45.00-70.00 %</td>
		</tr>
		<tr>
			<td>LYMPHOCYTES</td>
			<td style="text-align:center">{LYMP}</td>
			<td>%</td>
			<td>20.00-45.00 %</td>
		</tr>
		<tr>
			<td>EOSINOPHILS</td>
			<td style="text-align:center">{EOSIN}</td>
			<td>%</td>
			<td>1.00-8.00 %</td>
		</tr>
		<tr>
			<td>MONOCYTES</td>
			<td style="text-align:center">{MONO}</td>
			<td>%</td>
			<td>1.00-6.00 %</td>
		</tr>
		<tr>
			<td>BAND CELLS</td>
			<td style="text-align:center">{BAND}</td>
			<td>%</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>IMMATURE CELLS</td>
			<td style="text-align:center">{IMMATURE}</td>
			<td>%</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>RBC</td>
			<td style="text-align:center">{RBC}</td>
			<td>Million/cm</td>
			<td>3.50-5.00 Million/cm</td>
		</tr>
		<tr>
			<td>PCV</td>
			<td style="text-align:center">{PCV}</td>
			<td>%</td>
			<td>34.00 - 47.00 %</td>
		</tr>
		<tr>
			<td>MCV</td>
			<td style="text-align:center">{MCV}</td>
			<td>fl</td>
			<td>83.00-98.00 fl</td>
		</tr>
		<tr>
			<td>MCH</td>
			<td style="text-align:center">{mch}</td>
			<td>pg</td>
			<td>25.00-33.00 pg</td>
		</tr>
		<tr>
			<td>MCHC</td>
			<td style="text-align:center">{MCHC}</td>
			<td>g/dl</td>
			<td>28.00-36.00 g/dl</td>
		</tr>
		<tr>
			<td>PLATELET COUNT</td>
			<td style="text-align:center">{PLATELET}</td>
			<td>per cu mm</td>
			<td>150000-450000 per cu mm</td>
		</tr>
		<tr>
			<td>ESR</td>
			<td style="text-align:center">{ESR1}</td>
			<td>mm</td>
			<td>0.00-15.00 mm</td>
		</tr>
	</tbody>
</table>
', '6', '1', '1', '3028'),
('93', 'Indian link', NULL, '', '10', '1', '1', '2985'),
('94', 'Indian Ink ', NULL, '', '10', '1', '1', '0'),
('95', 'L.D.H.', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:48px; width:585px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">LDH</td>
			<td style="text-align:center; vertical-align:top">{LDH}</td>
			<td style="text-align:left; vertical-align:top">IU/L</td>
			<td style="text-align:left; vertical-align:top">114-2240 IU/L</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '287'),
('96', 'ORBIT', NULL, '', '4', '1', '1', '2897'),
('97', 'ASCITIC FLUID EXAMINATION', NULL, '<p><strong>Physical Examination</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
			<td><strong>Normal Value</strong></td>
		</tr>
		<tr>
			<td><strong>Appearance</strong></td>
			<td>{APP}</td>
			<td><em>CLEAR</em></td>
		</tr>
		<tr>
			<td><strong>Colour</strong></td>
			<td>{COLOUR}</td>
			<td><em>STRAW</em></td>
		</tr>
		<tr>
			<td><strong>Coagulum</strong></td>
			<td>{COALGU}</td>
			<td><em>ABSENT</em></td>
		</tr>
	</tbody>
</table>

<p><strong>Chemical Examination</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><strong>Observed Value</strong></td>
		</tr>
		<tr>
			<td><strong>Proteins</strong></td>
			<td>{PROTEINS}</td>
		</tr>
		<tr>
			<td><strong>Sugar</strong></td>
			<td>{SUGAR}</td>
		</tr>
	</tbody>
</table>

<p><strong>Microscopic Examination</strong><br />
Total Cell Count&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {TCC} /cmm&nbsp;&nbsp;&nbsp;&nbsp;</p>

<p>&nbsp;</p>

<table border="0" cellpadding="0" cellspacing="0" style="width:500px">
	<tbody>
		<tr>
			<td colspan="3"><strong>Differential Cell Count</strong></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Polymorphs</td>
			<td>{Polymorphs}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Lymphocytes</td>
			<td>{Lymphocyte}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Mesothelial</td>
			<td>{MESO}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>R.B.Cs</td>
			<td>{RBCS}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Gram Stain</td>
			<td>{GRAMSTAIN}</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Stain For A.F.B</td>
			<td>{AFB}</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '9', '1', '1', '410'),
('98', 'Indirect Coombs Test', NULL, '<p><em>Indirect Coombs Test&nbsp; :&nbsp; {</em>ICT}</p>
', '12', '1', '1', '291'),
('99', 'Urine for Ketone', NULL, '<p>Urine for ketone&nbsp;&nbsp;&nbsp; {UFK}</p>
', '8', '1', '1', '176'),
('100', 'MALARIA PARASITE', NULL, '<p>MALARIA PARASITE:&nbsp;&nbsp; {MALARIAP}</p>
', '6', '1', '1', '277'),
('101', 'BUN', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Blood Urea Nitrogen</td>
			<td style="text-align:center">{BUN}</td>
			<td>mg%</td>
			<td>6-24 mg%</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '392');

INSERT IGNORE INTO `lab_repo` (`mstRepoKey`, `Title`, `RTFData`, `HTMLData`, `GrpKey`, `IncludeHeader`, `IncludeFooter`, `charge_id`) VALUES
('102', 'Urine For Protien', NULL, '<p>Urine for Protein&nbsp; {UFP}</p>
', '8', '1', '1', '177'),
('103', 'Chikungunya IgM', NULL, '<p>Chikungunya IgM&nbsp;&nbsp;&nbsp; :&nbsp;&nbsp;&nbsp;&nbsp; {CIGM}</p>
', '1', '1', '1', '380'),
('104', 'WHOLE ABDOMEN', NULL, '<p><strong>LIVER:</strong> Is normal in size and echotexture. IHBR is normal.&nbsp;&nbsp; No Focal lesion is seen.<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></p>

<p><strong>GALL BLADDER:</strong> Normal Distention and wall thickness. No intraluminal echogenic with DAS is seen.</p>

<p><strong>C.B.D</strong>: Normal in Caliber. No obvious intraluminal echogenic mass is seen.</p>

<p><strong>PORTAL VEIN</strong>:&nbsp; WNL</p>

<p><strong>PANCREAS:</strong> Normal in size and echotexture MPD not dilated. No Focal lesion is seen.</p>

<p><strong>SPLEEN:</strong><strong> </strong>Normal in size and echotexture. No focal lesion is seen.</p>

<p><strong>KIDNEYS:</strong><strong> </strong>Left Kidney is normal in size, shape and echotexture. CMD maintained. No echogenic mass with DAS is seen.</p>

<p>Right Kidney is normal in size, shape and echotexture. CMD maintained. No echogenic mass with DAS is seen.</p>

<p><strong>URINARY BLADDER: </strong><strong>&nbsp;</strong>Normal Distention and wall thickness. No intra luminal mass is seen.</p>

<p><strong>PROSTATE:</strong> Is normal in volume. Capsule appears intact.</p>

<p><strong>UTERUS:</strong> Is normal in size and echotexture. Endometrial thickness is normal.</p>

<p>Bilateral Ovaries are normal.</p>

<p>No free fluid seen in upper peritoneal recesses and pelvis.</p>

<p>No pre or para aortic lymphadenopathy is seen.</p>

<p><strong>IMPRESSION</strong><strong>: - Normal Study.</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '8'),
('105', 'ULTRASOUND (OBST.)', NULL, '<ol>
	<li>Single alive intra uterine fetus is seen with normal cardiac and somatic activity.<strong>( at the time of examination)</strong></li>
	<li>Placenta is posterior and upper in position.</li>
	<li>Liquor is adequate.</li>
	<li>No Gross CVA is seen. Stomach Bubble, Fetal Kidneys and fetal UB normally seen.</li>
</ol>

<ul>
	<li><strong>B.P.D.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;: 7.68 cm&nbsp; 30&nbsp; weeks 6 days .(+/- 2 weeks)</li>
</ul>

<ul>
	<li><strong>H.C</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;: 27.87 cm 30 weeks 4 days. (+/- 2 weeks)</li>
</ul>

<ul>
	<li><strong>A.C</strong> &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;: 25.63 cm 29 weeks 6 days. (+/- 2 weeks)</li>
</ul>

<ul>
	<li><strong>F.L.</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;</strong>: 5.58 cm 29 weeks 3 days. (+/- 2 weeks)</li>
</ul>

<ul>
	<li><strong>F.W</strong>&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;: 1458 Gm.</li>
</ul>

<ul>
	<li><strong>F.H.R.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;: 150 beats/ minutes.</li>
</ul>

<p><strong>IMPRESSION:</strong><strong>&nbsp; Single alive intra uterine fetus of 30 week&rsquo;s 2 days is seen.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;</p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '9'),
('106', 'DEDICATED (OBS) ULTRASOUND', NULL, '<ol>
	<li>Single alive intra uterine fetus is seen with normal cardiac and somatic activity.<strong>( at the time of examination)</strong></li>
	<li>Placenta is anterior and upper in position.</li>
	<li>Liquor is adequate.</li>
</ol>

<p><strong>SKULL &amp; SPINE</strong>: -</p>

<ul>
	<li>Bilateral; cerebral hemispheres, basal ganglia, thalami, midbrain &amp; cerebellar hemispheres are normal</li>
	<li><strong>Bilateral nasal bone are normal</strong></li>
</ul>

<ul>
	<li><strong>No Gross CVA is seen. </strong></li>
</ul>

<ul>
	<li><strong>B.P.D.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : 3.77 cm&nbsp; 17&nbsp; weeks 4 days .(+/- 2 weeks)</li>
</ul>

<ul>
	<li><strong>H.C</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; : 13.34 cm 26weeks 6 days. (+/- 2 weeks)</li>
	<li><strong>TRANS - CEREBELLAR DIAMETER </strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;: </strong>1.75 cm</li>
</ul>

<ul>
	<li><strong>TRANS - ORBITAL DIAMETER</strong><strong>&nbsp;&nbsp; </strong>: 3.93cm</li>
	<li><strong>CISTERNA MAGNA</strong><strong> : </strong>&nbsp;3.9 mm</li>
	<li><strong>LATERAL VENTRICLE</strong> :- 6.3 mm</li>
</ul>

<p><strong>CHEST ABDOMEN &amp; LIMBS</strong></p>

<ul>
	<li>Stomach Bubble, Fetal Kidneys and fetal UB normally seen.</li>
	<li><strong><em>4 chamber view of the heart is normal.</em></strong></li>
</ul>

<ul>
	<li><strong>A.C</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;: 11.62 cm 17 weeks 3 days. (+/- 2 weeks)</li>
	<li><strong>F.L.</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>: 2.19 cm 16 weeks 4 days. (+/- 2 weeks)</li>
	<li><strong>F.W</strong>&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;: 170 Gm.</li>
	<li><strong>F.H.R.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : 144 beats/ minutes.<strong> </strong></li>
	<li><strong>HUMEROUS LENGTH </strong><strong>&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp; </strong>2.04 cm</li>
	<li><strong>TIBIA LENGTH </strong><strong>&nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>1.92 cm</li>
	<li><strong>RADIUS/ULNA LENGTH</strong><strong>&nbsp;&nbsp; :&nbsp;&nbsp;&nbsp; </strong>1.75 cm</li>
	<li><strong>UMBLICAL CORD</strong><strong> : </strong>3 vessel cord is seen with normal flow</li>
</ul>

<p><strong>IMPRESSION:</strong><strong>&nbsp; Single alive intra uterine fetus of 17 week&rsquo;s 6 days is seen with grossly normal parameters.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
', '15', '1', '1', '10'),
('107', 'DOPPLER (OBST.)', NULL, '<ul>
	<li>Single alive intra uterine fetus is seen in <strong><em>Cephalic Presentation </em></strong>with normal cardiac and somatic activity.&nbsp; <strong><em>(at the time of examination)</em></strong></li>
	<li>Placenta is posterior and upper.</li>
	<li>&nbsp;Liquor is adequate.</li>
	<li>No Gross CVA is seen. Stomach Bubble, Fetal Kidneys and fetal UB normally seen.</li>
</ul>

<ul>
	<li><strong>B.P.D.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : 9.28&nbsp; cm&nbsp; 37&nbsp; weeks 5 days .<strong>(+/- 2 weeks)</strong></li>
</ul>

<ul>
	<li><strong>F.L.</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>: 7.01 cm 36 weeks 0 days. <strong>(+/- 2 weeks)</strong></li>
</ul>

<ul>
	<li><strong>H.C</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; <strong>: 32.57 cm 36 weeks 6 day.</strong><strong>(+/- 2 weeks)</strong></li>
</ul>

<ul>
	<li><strong>A.C</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; : 28.90 cm 32 weeks 6 days. <strong>(+/- 2 weeks)</strong></li>
</ul>

<ul>
	<li><strong>F.H.R.</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; :&nbsp; 144 beats/ minutes.</li>
</ul>

<ul>
	<li><strong>F.W</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; :&nbsp; 2.5 Kg.</li>
</ul>

<ul>
	<li><strong>N.S.</strong><strong>T&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; : REACTIVE </strong></li>
</ul>

<ul>
	<li><strong>B.P.P</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : 10/10</li>
</ul>

<ul>
	<li>The umbilical artery shows normal flow pattern with forward diastolic flow.</li>
	<li>The uterine arteries show normal flow pattern with forward diastolic flow.</li>
	<li>The MCA flow is normal.</li>
	<li><strong><em>No cord is seen around the Neck.</em></strong></li>
</ul>

<p><strong>COLOUR DOPPLER PARAMETERS</strong></p>

<table border="1" cellspacing="0">
	<tbody>
		<tr>
			<td style="height:17.95pt; vertical-align:top; width:99.0pt">
			<p><strong><em>Vessels</em></strong></p>
			</td>
			<td style="height:17.95pt; vertical-align:top; width:1.25in">
			<h2><em>RI</em></h2>
			</td>
			<td style="height:17.95pt; vertical-align:top; width:85.5pt">
			<h2><em>PI</em></h2>
			</td>
			<td style="height:17.95pt; vertical-align:top; width:2.25in">
			<p><strong><em>S/D ratio</em></strong></p>
			</td>
		</tr>
		<tr>
			<td style="height:13.9pt; vertical-align:top; width:99.0pt">
			<p><strong>Umb Artery</strong></p>
			</td>
			<td style="height:13.9pt; vertical-align:top; width:1.25in">
			<p>0.47</p>
			</td>
			<td style="height:13.9pt; vertical-align:top; width:85.5pt">
			<p>0.54</p>
			</td>
			<td style="height:13.9pt; vertical-align:top; width:2.25in">
			<p>1.9</p>
			</td>
		</tr>
		<tr>
			<td style="height:26.05pt; vertical-align:top; width:99.0pt">
			<p><strong>Ut. artery (Rt.)</strong></p>
			</td>
			<td style="height:26.05pt; vertical-align:top; width:1.25in">
			<p>0.25</p>
			</td>
			<td style="height:26.05pt; vertical-align:top; width:85.5pt">
			<p>0.32</p>
			</td>
			<td style="height:26.05pt; vertical-align:top; width:2.25in">
			<p>No Pre diastolic notch</p>
			</td>
		</tr>
		<tr>
			<td style="height:8.25pt; vertical-align:top; width:99.0pt">
			<p><strong>Ut. artery (Lt.)</strong></p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:1.25in">
			<p>0.45</p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:85.5pt">
			<p>0.70</p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:2.25in">
			<p>No Pre diastolic notch</p>
			</td>
		</tr>
		<tr>
			<td style="height:8.25pt; vertical-align:top; width:99.0pt">
			<p><strong>MCA :</strong></p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:1.25in">
			<p>0.74</p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:85.5pt">
			<p>1.42</p>
			</td>
			<td style="height:8.25pt; vertical-align:top; width:2.25in">
			<p>--</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>Cerebro Placental Ratio &ndash;</strong><strong>&nbsp;&nbsp; R.I MCA /R.I Umb = 0.74/0.47&nbsp;&nbsp;&nbsp; (&gt;1)</strong></p>

<p><strong>IMPRESSION:</strong><strong>&nbsp; Single alive intra uterine fetus with normal C.D Parameters of 33 weeks 4 days.</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '11'),
('108', 'ULTRASOUND (LOWER ABDOMEN)', NULL, '<p><strong>URINARY BLADDER: </strong><strong>&nbsp;</strong>Normal distention and wall thickness. No intra luminal echogenic mass with DAS is seen.</p>

<p><strong>UTERUS:</strong> Is normal in size, antiverted in position and normal in echotexture. <strong><em>Endometrial thickness is 20.4 mm. No intra uterine G- Sac is seen.</em></strong></p>

<p><strong>OVARIES:</strong> Bilateral Ovaries are normal in size and echotexture.</p>

<p>No free fluid seen in pelvis.</p>

<p><strong>IMPRESSION:</strong> <strong>&nbsp;</strong><strong><em>USG findings S/O</em></strong><strong><em>.</em></strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '12'),
('109', 'ULTRASOUND (CHEST)', NULL, '<p><strong>LIVER:</strong> Is normal in size and echotexture. IHBR is normal.&nbsp;&nbsp; No Focal lesion is seen.<strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong></p>

<p><strong>PANCREAS:</strong> Normal in size and echotexture MPD not dilated. No Focal lesion is seen.</p>

<p><strong>SPLEEN:</strong><strong> </strong>Normal in size and echotexture. No focal lesion is seen.</p>

<p><strong><em>Moderate amount pleural effusion is seen on both sides.</em></strong></p>
', '15', '1', '1', '14'),
('110', 'ULTRASOUND (SCROTUM)', NULL, '<p><strong>LEFT TESTIS</strong></p>

<ul>
	<li>Left testis measures 3.87 X 3.48 X 2.81 cm in size with a volume of 19.81 ml.</li>
	<li>Is normal in size and echotexture.</li>
	<li>No focal lesion seen.</li>
	<li>No signs of varicocele seen.&nbsp;</li>
	<li>No significant amount of fluid is seen in the Tunica Vaginalis.&nbsp;</li>
	<li>Lt. Epididymis is normal in size and echopattern.</li>
</ul>

<p><strong>RIGHT TESTIS</strong></p>

<ul>
	<li>Right testis measures 3.87 X 3.48 X 2.81 cm in size with a volume of 19.81 ml.</li>
	<li>Is normal in size and echotexture.</li>
	<li>No focal lesion seen.</li>
	<li>No signs of varicocele seen.</li>
	<li>No significant amount of fluid is seen in the Tunica Vaginalis.&nbsp;</li>
	<li>Rt. Epididymis is normal in size and echopattern.</li>
	<li>Colour Doppler normal Flow of both Testis</li>
</ul>

<p><strong>IMPRESSION:</strong><strong> GROSSLY NORMAL STUDY.</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly Correlate Clinically</p>

<p>&nbsp;</p>
', '15', '1', '1', '15'),
('111', 'ULTRASOUND (EYE)', NULL, '<p><strong>High Resolution Sonography (12.5 MHz)</strong></p>

<p><strong>Both Coronal and Transverse scan taken</strong></p>

<p><strong>LT.EYE</strong><strong>: </strong></p>

<ul>
	<li><strong><em>Some settled echoes are seen within the post chamber</em></strong><strong>.</strong></li>
	<li>Anterior chamber is clear.</li>
	<li>Lens of Lt. Eye is normal.</li>
	<li>Retro Bulbar area is normal.</li>
	<li>Optic nerve is normal.</li>
</ul>

<p><strong>RT.EYE:</strong></p>

<ul>
	<li>Both anterior and posterior chamber are normal.</li>
	<li>Lens of Rt. Eye is normal.</li>
</ul>

<ul>
	<li>Optic nerve is normal.</li>
	<li>Retro Bulbar area is normal.</li>
</ul>

<p><strong>IMPRESSION :</strong> <strong>Small Left vitreous hemorrhage.</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '16'),
('112', 'ULTRASOUND (NECK)', NULL, '<p><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>&nbsp; (This Report is not valid for Medico Legal Purpose)</p>

<ul>
	<li>Right lobe measures 2.87 X 1.37 X 1.52 cm with a volume 2.87 ml.</li>
	<li>Left lobe measures 2.61 X 1.34 X 1.29 cm with a volume 2.15 ml.</li>
	<li>Shows normal echotexture.&nbsp; No calcification, nodule or cystic area seen.&nbsp; Outline is normal.</li>
	<li>Isthmus measures AP dimension 0.30 cm.</li>
</ul>

<ul>
	<li>Strap muscles are normal.</li>
</ul>

<ul>
	<li>Visualized CCA and IJV are normal on both sides.</li>
</ul>

<ul>
	<li>Trachea is central and shows characteristic reverberation artifacts.</li>
</ul>

<ul>
	<li>No cervical lymphadenopathy is seen.</li>
</ul>

<p><strong>IMPRESSION:</strong></p>

<p><br />
&nbsp;</p>
', '15', '1', '1', '17'),
('113', 'ULTRASOUND (FOLLICULAR MONITORING)', NULL, '<h1><strong>ULTRASOUND </strong><strong>(FOLLICULAR MONITORING)</strong></h1>

<p>&nbsp;</p>

<table align="left" border="1" cellspacing="0" style="width:487.55pt">
	<tbody>
		<tr>
			<td style="height:30.85pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp; S. No</p>
			</td>
			<td style="height:30.85pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp; Date</p>
			</td>
			<td style="height:30.85pt; vertical-align:top; width:61.3pt">
			<p>&nbsp;&nbsp; Day</p>
			</td>
			<td style="height:30.85pt; vertical-align:top; width:73.2pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ETT</p>
			</td>
			<td style="height:30.85pt; vertical-align:top; width:99.0pt">
			<p>Rt. Follicle</p>
			</td>
			<td style="height:30.85pt; vertical-align:top; width:95.15pt">
			<p>Lt. Follicle</p>
			</td>
		</tr>
		<tr>
			<td style="height:.4in; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1)</p>
			</td>
			<td style="height:.4in; vertical-align:top; width:79.45pt">
			<p>01/11/2018</p>
			</td>
			<td style="height:.4in; vertical-align:top; width:61.3pt">
			<p>9th</p>
			</td>
			<td style="height:.4in; vertical-align:top; width:73.2pt">
			<p>5.1 mm</p>
			</td>
			<td style="height:.4in; vertical-align:top; width:99.0pt">
			<p>9.7 X 10.6 mm</p>
			</td>
			<td style="height:.4in; vertical-align:top; width:95.15pt">
			<p>6.8 X 6.4 mm</p>
			</td>
		</tr>
		<tr>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2)</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:61.3pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:73.2pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:99.0pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:95.15pt">
			<p>&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 3)</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:61.3pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:73.2pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:99.0pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:95.15pt">
			<p>&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 4)</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:61.3pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:73.2pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:99.0pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:95.15pt">
			<p>&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 5)</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:79.45pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:61.3pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:73.2pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:99.0pt">
			<p>&nbsp;</p>
			</td>
			<td style="height:30.45pt; vertical-align:top; width:95.15pt">
			<p>&nbsp;</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>
', '15', '1', '1', '18'),
('114', 'SONO MAMOGRAPHY (BREAST)', NULL, '<p><strong>RIGHT BREAST:</strong></p>

<ul>
	<li>Nipple including areola is normal.</li>
	<li>There is no solid or cystic mass on Rt. side.</li>
	<li>Retro mammary space is normal.</li>
	<li>Axillary area is normal.</li>
</ul>

<p><strong>LEFT BREAST:</strong></p>

<ul>
	<li>There is no solid or cystic mass on Lt. Side.</li>
	<li>Nipple including areola is normal.</li>
</ul>

<ul>
	<li>&nbsp;&nbsp;Retro mammary space is normal.</li>
	<li>&nbsp;&nbsp;Axillary area is normal.</li>
	<li>&nbsp;&nbsp;No Lymphadenopathy is seen in Axillary region.</li>
</ul>

<p><strong>IMPRESSION:</strong> <strong>Grossly normal study.</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>
', '15', '1', '1', '20'),
('115', 'ULTRASOUND GUIDED (ASPIRATION)', NULL, '<ul>
	<li><strong>Aspiration Right pleural effusion is done under L.A using all antiseptic precautions.</strong></li>
	<li><strong>About 500 ml of fluid is aspirated &amp; sent for microscopy.</strong></li>
	<li><strong>Minimal amount pleural effusion is seen on Right side.</strong></li>
</ul>
', '15', '1', '1', '21'),
('116', 'DUPLEX DOPPLER BILATERAL LOWER LIMBS', NULL, '<p><strong><em>Colour flow and duplex scanning of the B/L lower limbs was performed using 10-12 mhz linear transducer.</em></strong></p>

<ul>
	<li>The common femoral and Femoral Veins are normally visualized. These are normal in caliber and show normal colour flow.</li>
	<li>These show normal response to Valsalva and Augmentation.</li>
	<li>These veins show normal compressibility and respiratory phasicity.</li>
	<li>No Thrombus is seen.&nbsp;</li>
	<li>Popliteal vein is normal in caliber and show normal response to Augmentation.</li>
	<li>No spontaneous flow is seen in the PTV &amp; ATV.</li>
	<li>Normal flow is seen on augmentation.</li>
	<li>Distal veins could not be evaluated.</li>
</ul>

<p><strong>IMPRESSION:</strong><strong> &nbsp;&nbsp;&nbsp;&nbsp;<em>NO EVIDENCE OF DVT.</em></strong></p>
', '15', '1', '1', '22'),
('117', 'DUPLEX DOPPLER RIGHT LOWER LIMB', NULL, '<p><strong><em>Colour flow and duplex scanning of the RT. Lower limb was performed using 10-12 mhz linear transducer.</em></strong></p>

<p>&nbsp;</p>

<ul>
	<li>The common femoral artery on real time shows normal branching into the superficial femoral and profunda femoral arteries vessel caliber is normal.</li>
</ul>

<p>&nbsp;</p>

<ul>
	<li>&nbsp;No wall abnormality is seen.</li>
</ul>

<p>&nbsp;</p>

<ul>
	<li>&nbsp;On the duplex mode normal triphasic flow is seen .This is consistent with a high peripheral resistance.</li>
</ul>

<p>&nbsp;</p>

<ul>
	<li>Colour flow also reveals complete uniform filling with no evidence of any turbulence in CFA, SFA, and Popliteal Arteries.</li>
</ul>

<p>&nbsp;</p>

<ul>
	<li>Anterior tibial, posterior tibial and peroneal arteries show decreased flow and can be traced up to ankle.</li>
</ul>

<p>&nbsp;</p>

<p><strong>IMPRESSION </strong><strong>: </strong><strong>COLOUR DOPPLER S/O GROSSLY NORMAL STUDY.</strong></p>

<h2>&nbsp;</h2>
', '15', '1', '1', '23'),
('118', 'DOPPLER STUDY LEFT CAROTID', NULL, '<p><strong><em>Colour flow and pulse Doppler was performed of Left the carotid arteries using a 5-7.5 MHz linear transducer in the longitudinal and transverse planes.</em></strong></p>

<p>&nbsp;</p>

<ul>
	<li>On the B mode images the left common carotid, carotid bulb, internal and external carotid show normal luminal diameter with wall.</li>
	<li><strong>Average IMT is 0.1cm. &nbsp;</strong></li>
	<li>Colour flow studies demonstrate normal pulsatile laminar flow with no evidence of any turbulence.</li>
	<li>Pulse Doppler reveals a normal flow pattern.&nbsp; Peak systolic velocities are in the normal range.</li>
</ul>

<ul>
	<li>Lt. vertebral artery is normal.</li>
	<li><em>&nbsp;Due to high division of CCA only small part of ICA is visible.</em></li>
</ul>

<p>&nbsp;</p>

<table cellspacing="0">
	<tbody>
		<tr>
			<td rowspan="2" style="vertical-align:top; width:121.55pt">
			<p>Artery</p>
			</td>
			<td colspan="2" style="vertical-align:top; width:168.3pt">
			<p>Left</p>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:top; width:84.15pt">
			<p>PSV (cms/sec)</p>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>EDV (cms/sec)</p>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:top; width:121.55pt">
			<ul>
				<li>Common Carotid</li>
			</ul>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>&nbsp;67.2</p>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>8.7</p>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:top; width:121.55pt">
			<ul>
				<li>Internal Carotid</li>
			</ul>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>42</p>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>12.7</p>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:top; width:121.55pt">
			<ul>
				<li>Lt. Vertebral</li>
			</ul>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>19.4</p>
			</td>
			<td style="vertical-align:top; width:84.15pt">
			<p>7</p>
			</td>
		</tr>
	</tbody>
</table>

<p><strong>IMPRESSION:</strong></p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly Correlate Clinically.</p>
', '15', '1', '1', '24'),
('119', 'ULTRASOUND SWELLING', NULL, '<p><strong><em>Ultrasound swelling is done using 8MHz linear transducer</em></strong>.</p>

<p><strong><em>There is visualized a hetroechoic soft tissue swelling in the knee region. The swelling show vascularity. Soft tissue mass. ? Malignant</em></strong></p>

<p>The muscles &amp; tendons of the visualized area are grossly normal.</p>
', '15', '1', '1', '26'),
('120', 'ULTRASOUND (CRANIUM)', NULL, '<ul>
	<li>Bilateral Cerebral Hemispheres are grossly normal in echogenicity. No focal lesion is seen.</li>
	<li>Bilateral lateral Ventricles and 3rd are grossly normal.</li>
	<li>Bilateral Thalami and Mid Brain are grossly normal.</li>
	<li>Visualized part of Cerebellum is normal.</li>
	<li>No area of altered echogenicity is seen.</li>
</ul>

<p><strong>&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; </strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kindly correlate clinically.</p>

<h2>&nbsp;</h2>
', '15', '1', '1', '28'),
('121', 'DOPPLER STUDY RIGHT CAROTID', NULL, '<p><strong><em>Colour flow and pulse Doppler was performed of Right the carotid arteries using a 5-7.5 MHz linear transducer in the longitudinal and transverse planes.</em></strong></p>

<p>&nbsp;</p>

<p><strong>Right Carotid :</strong></p>

<ul>
	<li>On the B mode images the right common carotid, carotid bulb, internal and external carotid show normal luminal diameter .The IMT is 0.6 mm with wall. No plaque is noted.</li>
	<li>Colour flow studies demonstrate normal pulsatile laminar flow with no evidence of any turbulence.</li>
	<li>Pulse Doppler reveals a normal flow pattern; Peak systolic velocities are in the normal range.</li>
	<li>Rt. vertebral artery is normal in Caliber and shows normal colour flow.</li>
</ul>

<p>&nbsp;
<table border="1" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td colspan="1" rowspan="2" style="text-align:center; vertical-align:middle">Artery</td>
			<td style="text-align:center; vertical-align:middle">RT</td>
			<td style="text-align:center; vertical-align:middle">LT</td>
		</tr>
		<tr>
			<td style="text-align:center; vertical-align:middle">PSV (cms/sec)</td>
			<td style="text-align:center; vertical-align:middle">EDV (cm/sec)</td>
		</tr>
		<tr>
			<td style="text-align:center; vertical-align:middle">
			<ul>
				<li>common Carotid</li>
			</ul>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:center; vertical-align:middle">
			<ul>
				<li>Internal Carotid</li>
			</ul>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:center; vertical-align:middle">Rt. Vertebral</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
</p>

<p><strong>IMPRESSION:</strong> &nbsp;&nbsp;<strong>Normal study.</strong></p>
', '15', '1', '1', '3084'),
('122', 'NCCT HEAD', NULL, '<p>&nbsp;&nbsp;</p>

<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td style="text-align:center">&nbsp;(This report is not valid for Medico Legal Purpose)</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong><em>7 mm contiguous axial sections were obtained of the entire skull from the base to vertex.</em></strong></p>

<p><strong>Clinical history</strong>: Headache, vomiting, vertigo, head injury, RTA, LOC.</p>

<ul>
	<li>No EDH, SDH, SAH or ICH is seen.</li>
	<li>Bilateral Cerebral Hemispheres are normal.</li>
	<li>Bilateral lateral Ventricles and 3rd Ventricle are normal.</li>
	<li>Bilateral Basal Ganglia and Thalami are normal.</li>
	<li>Mid brain and Pons are normal.</li>
	<li>4th Ventricle is normal in size and midline in position.</li>
	<li>Bilateral Cerebellar Hemisphere is normal in size and Contour.</li>
	<li>Extra Axial fluid spaces and Basal Cisterns are normal.</li>
	<li>Bone window &ndash; No Obvious fracture is seen.</li>
</ul>
', '15', '1', '1', '1'),
('123', 'CECT ABDOMEN', NULL, '<p><strong><em>7 mm scans of the abdomen obtained before and after the administration of Oral &amp; I/V contrast. Oral contrast given to opacify bowel loops.</em></strong></p>

<p>&nbsp;</p>

<p><strong><em>The study reveals:-</em></strong></p>

<h2>&nbsp;</h2>

<p><strong>LIVER:</strong> Measures 15.7 cm in cranio-caudal dimensions in mid clavicular line. No focal Parenchymal abnormality is seen.&nbsp; Hepatic vessels are normal.&nbsp;</p>

<p>&nbsp;</p>

<p><strong>GALL BLADDER:</strong> No mass lesion is seen in Gall Bladder Fossa. Peri cholecystatic fat planes are maintained.</p>

<p>&nbsp;</p>

<p><strong>C.B.D:</strong>&nbsp; Is within normal limit.</p>

<p>&nbsp;</p>

<p><strong>PORTAL VEIN:</strong> Is normal in caliber and course. It shows normal enhancement. No Thrombus is seen. No Varices are seen at Porta.</p>

<p>&nbsp;</p>

<p><strong>PANCREAS:</strong> Is normal in size and outline. Parenchymal enhancement is homogeneous with no evidence of any focal lesion or calcification. Pancreatic duct is not dilated. Peripancreatic fat planes are maintained.&nbsp; Spleno-portal axis is normal.&nbsp;&nbsp;</p>

<p>&nbsp;</p>

<p><strong>SPLEEN:</strong> Spleen is normal in size and shows homogeneous contrast enhancement.&nbsp; No focal lesion is seen.&nbsp;</p>

<p>&nbsp;</p>

<p><strong>KIDNEYS &amp; ADRENAL:</strong> Bilateral kidneys are normal in size, shape and position.&nbsp; Normal enhancement of the parenchyma is seen.&nbsp; Pelvi-calyceal system is well opacified.</p>

<p>No evidence of any filling defect or distortion on either Side.</p>

<p>Both adrenals are normal.</p>

<p>&nbsp;</p>

<p><strong>URINARY BLADDER:</strong> Is grossly normal. No Focal lesion is seen.</p>

<p>&nbsp;</p>

<p><strong>PROSTATE:</strong><strong>- </strong>It shows homogenous enhancement. Fat plains between Prostate, Urinary Bladder, Seminal Vesicles and Rectum are maintained.</p>

<ul>
	<li>No Pre or Para aortic Lymphadenopathy is seen.</li>
	<li>Gut is normally Opacified by oral and rectal contrast.</li>
	<li>No free fluid is seen in all peritoneal recesses.</li>
	<li>No pleural effusion is seen.</li>
</ul>

<p>&nbsp;</p>

<p><strong>On Bone Window</strong><strong>: - No Focal lytic / Sclerotic lesion is seen in the visualized part.&nbsp; </strong></p>

<p>&nbsp;</p>

<p><strong>IMPRESSION:</strong><strong>-&nbsp; <em>GROSSLY NORMAL STUDY</em></strong></p>

<p><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>Kindly Correlate Clinically.</p>
', '15', '1', '1', '71'),
('124', 'CECT THORAX', NULL, '<p><strong><em>5 mm contiguous axial sections were obtained of the thorax from Apex to Adrenal region after I.V Contrast.</em></strong></p>

<p>&nbsp;</p>

<p><strong>C.E.C.T CHEST REVEALS:-</strong></p>

<p>&nbsp;</p>

<ul>
	<li>Bilateral Lung fields are grossly normal.</li>
	<li>No area of collapse consolidation is seen.</li>
	<li>No evidence of Bronchiectasis is seen.&nbsp;</li>
	<li>No significant size Mediastinal lymphadenopathy is seen.</li>
	<li>Mediastinal vascular structures are normally opacified by I.V contrast.</li>
	<li>No pleural effusion is seen</li>
	<li>Visualized parts of upper abdominal Viscera are normal.</li>
	<li>On bone window- No focal- Lytic/ Sclerotic lesion is seen in the visualized part.</li>
</ul>

<p>&nbsp;</p>

<p><strong>IMPRESSION:</strong>&nbsp;</p>

<p><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>Kindly Correlate Clinically.</p>

<p>&nbsp;</p>
', '15', '1', '1', '38'),
('125', 'NON-CONTRAST MRI OF THE BRAIN ', NULL, '<p>&nbsp;</p>

<table border="0" cellpadding="1" cellspacing="1" style="height:91px; width:469px">
	<tbody>
		<tr>
			<td style="text-align:center; vertical-align:middle; width:100%"><strong>NON-CONTRAST MRI OF THE BRAIN </strong></td>
		</tr>
	</tbody>
</table>

<p><strong>TECHNIQUE:</strong> Multiplanar T1, T2, FLAIR, ADC, DWI, SWI scans of the brain were performed.</p>

<p>&nbsp;</p>

<p><strong>FINDINGS:-</strong></p>

<p>&nbsp;</p>

<p>CEREBRAL PARENCHYMA: Normal.&nbsp;</p>

<p>BASAL GANGLIA, THALAMI: Normal.&nbsp;&nbsp;&nbsp;</p>

<p>INTERNAL CAPSULE:&nbsp;&nbsp;&nbsp; Normal.&nbsp;</p>

<p>VENTRICLES:&nbsp;&nbsp; Normal.&nbsp;</p>

<p>SULCI and BASAL CISTERNS:&nbsp; Normal.&nbsp;&nbsp;&nbsp;</p>

<p>MIDLINE SHIFT/ MASS EFFECT:&nbsp; nil significant&nbsp;&nbsp;</p>

<p>CEREBELLUM:&nbsp;&nbsp; Normal.&nbsp;</p>

<p>MIDBRAIN, PONS, MEDULLA:&nbsp;&nbsp;&nbsp; Normal.&nbsp;</p>

<p>CV JUNCTION:&nbsp;&nbsp; Normal.&nbsp;&nbsp;</p>

<p>SELLA:&nbsp;&nbsp;&nbsp; Normal.&nbsp;</p>

<p>ORBITS:&nbsp;&nbsp;&nbsp; Normal.&nbsp;</p>

<p>DURAL VENOUS SINUSES:&nbsp; Normal.&nbsp;</p>

<p>VISUALISED INTRACRANIAL ARTERIES:&nbsp;&nbsp; Normal.&nbsp;</p>

<p>PARANASAL SINUSES and MASTOID AIR CELLS:&nbsp; Normal.&nbsp;&nbsp;&nbsp;</p>

<p>BONES: Normal.&nbsp;</p>

<p>&nbsp;</p>

<p><strong>IMPRESSION:</strong></p>

<p>&nbsp;Yrs old male with history of headache, vertigo, vomiting.</p>

<p>&nbsp;</p>

<p><strong>Non-contrast MRI study of the brain.</strong></p>

<p>&nbsp;</p>

<ol>
	<li><strong><em>No significant radiological abnormality.</em></strong></li>
</ol>

<p>&nbsp;</p>

<p>Recommend clinical correlation.</p>

<p>&nbsp;
<p>&nbsp;</p>
</p>

<p>Investigations have their limitations. Solitary pathological/Radiological and other investigations never confirm the final diagnosis. They only help in diagnosing the disease in correlation to clinical symptoms and other related tests. Please interpret accordingly.<br />
<br />
This Report is not for Medico - Legal Purposes</p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p>&nbsp;</p>
', '15', '1', '1', '3'),
('126', 'NCCT FACE (AXIAL WITH 3D RECON)', NULL, '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>&nbsp;(This report is not valid for Medico Legal Purpose)</td>
		</tr>
	</tbody>
</table>

<p><strong>Axial Plain Scan done with 1 mm Slice Thickness.</strong></p>

<ul>
	<li>Bilateral Ethmoid, Sphenoid and Maxillary Sinuses are clear.</li>
	<li>Bilateral OMCs are patent.</li>
	<li>Both Inferior and middle turbinates are grossly normal.</li>
	<li>Insignificant DNS is seen with convexity towards Lt.</li>
	<li>Bilateral uncinate processes are normal.</li>
	<li>Bilateral upper and lower alveolar margins are intact.</li>
	<li>Bilateral T.M joints are grossly normal.</li>
	<li>Visualized parts of bony orbits are normal.</li>
	<li>No Obvious Fracture is seen in the visualized part.</li>
</ul>
', '15', '1', '1', '36'),
('127', 'NCCT RT. SHOULDER', NULL, '<p>(This report is not valid for Medico Legal Purpose)</p>

<p>&nbsp;</p>

<p><strong><em>2 mm contiguous axial sections were obtained of the entire Rt. Shoulder.</em></strong></p>

<p>&nbsp;</p>

<ul>
	<li>The head, surgical neck, anatomical neck, lesser tuberosity and greater tuberosity of humerus are normal.</li>
	<li>Visualized part of clavicle is normal.</li>
	<li>Visualized part of acromian process, coracoid process and body of Scapula are normal.</li>
	<li>The articular margins of Glenoid cavity are regular &amp; smooth.</li>
	<li>The Gleno-humoral joint is normal.</li>
	<li>The shape of Humoral head is maintained with smooth articular surface.</li>
</ul>

<p>&nbsp;</p>

<p>Kindly Correlate Clinically.</p>

<p>&nbsp;</p>

<h2>&nbsp;</h2>

<p>&nbsp;</p>
', '15', '1', '1', '3027'),
('128', 'OP', NULL, '<p>&nbsp;</p>

<p>INTERPRETATION</p>

<table border="0" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td>Induration Less than 5 mm</td>
			<td>Negative</td>
		</tr>
		<tr>
			<td>Induration greater then 10 mm</td>
			<td>Positive</td>
		</tr>
		<tr>
			<td>Induration between 9-10 mm</td>
			<td>Birderline</td>
		</tr>
	</tbody>
</table>
', '15', '1', '1', '270'),
('129', 'SERUM PHOSPHORUS', NULL, '<table border="0" cellpadding="0" cellspacing="0" style="height:26px; width:635px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>SERUM PHOSPHORUS</td>
			<td>{SPHOSPHORUS}</td>
			<td>mg/dl</td>
			<td>2.5-5.0</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '227'),
('130', 'Ionized Calcium', NULL, '<table border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></td>
			<td><strong>Unit&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td>Ionized Calcium</td>
			<td>{ICALCIUM}</td>
			<td>mg/dl</td>
			<td>4.4-5.5</td>
		</tr>
	</tbody>
</table>
', '1', '1', '1', '290'),
('131', 'Glucose Random*', NULL, '<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio.Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Glucose Random*</strong><br />
			Sample: Plasma</td>
			<td>{GR}</td>
			<td>mg/dl</td>
			<td>70-140</td>
			<td>GOD POD</td>
		</tr>
	</tbody>
</table>


<p>&nbsp;</p>
', '1', '1', '1', '343'),
('132', 'Nirogi kaya Basic Package', NULL, '<p><strong>COMPLETE BLOOD COUNT (CBC)</strong></p>

<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td><strong>Haemoglobin</strong></td>
			<td>{HB}</td>
			<td>gm%</td>
			<td>12-16</td>
		</tr>
		<tr>
			<td><strong>TLC</strong></td>
			<td>{TLC}</td>
			<td>per cu mm</td>
			<td>4000-11000</td>
		</tr>
		<tr>
			<td><strong>DLC</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><strong>Neutrophils</strong></td>
			<td>{NEU}</td>
			<td>%</td>
			<td>45-70</td>
		</tr>
		<tr>
			<td><strong>Lymphocytes</strong></td>
			<td>{LYMP}</td>
			<td>%</td>
			<td>20-45</td>
		</tr>
		<tr>
			<td><strong>Eosinophils</strong></td>
			<td>{EOSIN}</td>
			<td>%</td>
			<td>1-8</td>
		</tr>
		<tr>
			<td><strong>Monocytes</strong></td>
			<td>{MONO}</td>
			<td>%</td>
			<td>1-6</td>
		</tr>
		<tr>
			<td><strong>Basophiles</strong></td>
			<td>{BASO}</td>
			<td>%</td>
			<td>&lt; 1%</td>
		</tr>
		<tr>
			<td><strong>RBC&nbsp;Count</strong></td>
			<td>{RBC}</td>
			<td>Million/cm</td>
			<td>4.2-5.5</td>
		</tr>
		<tr>
			<td><strong>PCV</strong></td>
			<td>{PCV}</td>
			<td>%</td>
			<td>40 - 54</td>
		</tr>
		<tr>
			<td><strong>MCV</strong></td>
			<td>{MCV}</td>
			<td>fl</td>
			<td>80-100</td>
		</tr>
		<tr>
			<td><strong>MCH</strong></td>
			<td>{mch}</td>
			<td>pg</td>
			<td>28-35</td>
		</tr>
		<tr>
			<td><strong>MCHC</strong></td>
			<td>{MCHC}</td>
			<td>g/dl</td>
			<td>30-38</td>
		</tr>
		<tr>
			<td><strong>Platelet Count</strong></td>
			<td>{PLATELET}</td>
			<td>per cu mm</td>
			<td>1.5-4.5</td>
		</tr>
		<tr>
			<td><strong>ESR</strong></td>
			<td>{ESR1}</td>
			<td>Mm for 1st hr.</td>
			<td>&lt; 9 &gt;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>Glucose Fasting / PP</strong></p>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td style="text-align:center"><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Glucose Fasting*</strong><br />
			Sample:Plasma</td>
			<td style="text-align:center">{BGF}</td>
			<td>mg/dl</td>
			<td>&lt; 99 Normal<br />
			100-125 Near Normal<br />
			I.G.T)<br />
			&gt; 126 Diabetic&nbsp;</td>
			<td>GOD POD</td>
		</tr>
		<tr>
			<td><strong>Glucose PP*</strong><br />
			Sample:Plasma&nbsp;after meal</td>
			<td style="text-align:center">{BGPP}</td>
			<td>mg/dl</td>
			<td>0-140</td>
			<td>GOD POD</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>
			<p><strong>Test Name</strong></p>
			</td>
			<td>
			<p><strong>Result</strong></p>
			</td>
			<td>
			<p><strong>Unit</strong></p>
			</td>
			<td>
			<p><strong>Bio.Ref.Interval</strong></p>
			</td>
			<td>
			<p><strong>Method</strong></p>
			</td>
		</tr>
		<tr>
			<td>
			<p><strong>GLYCOSYLATED HAEMOGLOBIN (HBA1C) *</strong>, EDTA BLOOD<br />
			Glycosylated Haemoglobin (HbA1c)</p>
			</td>
			<td>
			<p>{HBA1C}</p>
			</td>
			<td>
			<p>%</p>
			</td>
			<td>
			<p>&lt; 5.7 Non diabetic<br />
			adults&gt;=18 years<br />
			5.7-6.4 At risk<br />
			(Prediabetes)<br />
			&gt;= 6.5 Diabetes<br />
			(As per ADA)</p>
			</td>
			<td>
			<p>QUANTITATIVE TURBIDIMETRIC IMMUNOASSAY</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p><strong>LIPID PROFILE</strong></p>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey"><strong>Result</strong></td>
			<td style="background-color:lightgrey"><strong>Bio. Ref.Interval</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">Cholesterol(Total)</td>
			<td style="vertical-align:top">{l_Choleste}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt;200 Desirable<br />
			200-239 Borderline High<br />
			&gt; 240 High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">Triglyceride</td>
			<td style="vertical-align:top">{l_Triglyce}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 150 Normal<br />
			150-199 Borderline High<br />
			200-499 High<br />
			&gt;500 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">HDL&nbsp;Cholesterol ( Good Cholesterol )</td>
			<td style="vertical-align:top">{l_hdl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">30-70</td>
		</tr>
		<tr>
			<td style="vertical-align:top">LDL&nbsp;Cholesterol ( Bad Cholesterol ) ( Direct )</td>
			<td style="vertical-align:top">{l_ldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 100 Optimal<br />
			100-129 Nr.<br />
			Optimal/Above Optimal<br />
			130-159 Borderline High<br />
			160-189 High<br />
			&gt; 190 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">VLDL</td>
			<td style="vertical-align:top">{l_vldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">10-33</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>T3,T4,TSH</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="height:48px; width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Triiodothyronine [T3]</td>
			<td style="text-align:center; vertical-align:top">{T3}</td>
			<td style="text-align:left; vertical-align:top">ng/ml</td>
			<td style="text-align:left; vertical-align:top">0.00-1.51 ng/ml</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroxine [T4]</td>
			<td style="text-align:center; vertical-align:top">{T4}</td>
			<td style="text-align:left; vertical-align:top">ug/dl</td>
			<td style="text-align:left; vertical-align:top">4.66-9.32 ug/dl</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroid Stimulating Hormone (TSH)</td>
			<td style="text-align:center; vertical-align:top">{TSH}</td>
			<td style="text-align:left; vertical-align:top">ulU/mL</td>
			<td style="text-align:left; vertical-align:top">0.25-5.00 ulU/mL</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '15', '1', '1', '3477'),
('133', 'Nirogi kaya Advanced Package', NULL, '<p><strong>COMPLETE BLOOD COUNT (CBC)</strong></p>

<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td><strong>Haemoglobin</strong></td>
			<td>{HB}</td>
			<td>gm%</td>
			<td>12-16</td>
		</tr>
		<tr>
			<td><strong>TLC</strong></td>
			<td>{TLC}</td>
			<td>per cu mm</td>
			<td>4000-11000</td>
		</tr>
		<tr>
			<td><strong>DLC</strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><strong>Neutrophils</strong></td>
			<td>{NEU}</td>
			<td>%</td>
			<td>45-70</td>
		</tr>
		<tr>
			<td><strong>Lymphocytes</strong></td>
			<td>{LYMP}</td>
			<td>%</td>
			<td>20-45</td>
		</tr>
		<tr>
			<td><strong>Eosinophils</strong></td>
			<td>{EOSIN}</td>
			<td>%</td>
			<td>1-8</td>
		</tr>
		<tr>
			<td><strong>Monocytes</strong></td>
			<td>{MONO}</td>
			<td>%</td>
			<td>1-6</td>
		</tr>
		<tr>
			<td><strong>Basophiles</strong></td>
			<td>{BASO}</td>
			<td>%</td>
			<td>&lt; 1%</td>
		</tr>
		<tr>
			<td><strong>RBC&nbsp;Count</strong></td>
			<td>{RBC}</td>
			<td>Million/cm</td>
			<td>4.2-5.5</td>
		</tr>
		<tr>
			<td><strong>PCV</strong></td>
			<td>{PCV}</td>
			<td>%</td>
			<td>40 - 54</td>
		</tr>
		<tr>
			<td><strong>MCV</strong></td>
			<td>{MCV}</td>
			<td>fl</td>
			<td>80-100</td>
		</tr>
		<tr>
			<td><strong>MCH</strong></td>
			<td>{mch}</td>
			<td>pg</td>
			<td>28-35</td>
		</tr>
		<tr>
			<td><strong>MCHC</strong></td>
			<td>{MCHC}</td>
			<td>g/dl</td>
			<td>30-38</td>
		</tr>
		<tr>
			<td><strong>Platelet Count</strong></td>
			<td>{PLATELET}</td>
			<td>per cu mm</td>
			<td>1.5-4.5</td>
		</tr>
		<tr>
			<td><strong>ESR</strong></td>
			<td>{ESR1}</td>
			<td>Mm for 1st hr.</td>
			<td>&lt; 9 &gt;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>Glucose Fasting / PP</strong></p>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td style="text-align:center"><strong>Result</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Bio. Ref. Interval</strong></td>
			<td><strong>Method</strong></td>
		</tr>
		<tr>
			<td><strong>Glucose Fasting*</strong><br />
			Sample:Plasma</td>
			<td style="text-align:center">{BGF}</td>
			<td>mg/dl</td>
			<td>&lt; 99 Normal<br />
			100-125 Near Normal<br />
			I.G.T)<br />
			&gt; 126 Diabetic&nbsp;</td>
			<td>GOD POD</td>
		</tr>
		<tr>
			<td><strong>Glucose PP*</strong><br />
			Sample:Plasma&nbsp;after meal</td>
			<td style="text-align:center">{BGPP}</td>
			<td>mg/dl</td>
			<td>0-140</td>
			<td>GOD POD</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
	<tbody>
		<tr>
			<td>
			<p><strong>Test Name</strong></p>
			</td>
			<td>
			<p><strong>Result</strong></p>
			</td>
			<td>
			<p><strong>Unit</strong></p>
			</td>
			<td>
			<p><strong>Bio.Ref.Interval</strong></p>
			</td>
			<td>
			<p><strong>Method</strong></p>
			</td>
		</tr>
		<tr>
			<td>
			<p><strong>GLYCOSYLATED HAEMOGLOBIN (HBA1C) *</strong>, EDTA BLOOD<br />
			Glycosylated Haemoglobin (HbA1c)</p>
			</td>
			<td>
			<p>{HBA1C}</p>
			</td>
			<td>
			<p>%</p>
			</td>
			<td>
			<p>&lt; 5.7 Non diabetic<br />
			adults&gt;=18 years<br />
			5.7-6.4 At risk<br />
			(Prediabetes)<br />
			&gt;= 6.5 Diabetes<br />
			(As per ADA)</p>
			</td>
			<td>
			<p>QUANTITATIVE TURBIDIMETRIC IMMUNOASSAY</p>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>LIPID PROFILE</strong></p>

<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey"><strong>Result</strong></td>
			<td style="background-color:lightgrey"><strong>Bio. Ref.Interval</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">Cholesterol(Total)</td>
			<td style="vertical-align:top">{l_Choleste}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt;200 Desirable<br />
			200-239 Borderline High<br />
			&gt; 240 High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">Triglyceride</td>
			<td style="vertical-align:top">{l_Triglyce}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 150 Normal<br />
			150-199 Borderline High<br />
			200-499 High<br />
			&gt;500 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">HDL&nbsp;Cholesterol ( Good Cholesterol )</td>
			<td style="vertical-align:top">{l_hdl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">30-70</td>
		</tr>
		<tr>
			<td style="vertical-align:top">LDL&nbsp;Cholesterol ( Bad Cholesterol ) ( Direct )</td>
			<td style="vertical-align:top">{l_ldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">&lt; 100 Optimal<br />
			100-129 Nr.<br />
			Optimal/Above Optimal<br />
			130-159 Borderline High<br />
			160-189 High<br />
			&gt; 190 Very High</td>
		</tr>
		<tr>
			<td style="vertical-align:top">VLDL</td>
			<td style="vertical-align:top">{l_vldl}&nbsp;mg/dl</td>
			<td style="vertical-align:top">10-33</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>T3,T4,TSH</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="height:48px; width:100%">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Observed Values</strong></td>
			<td><strong>Unit</strong></td>
			<td><strong>Normal Values</strong></td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Triiodothyronine [T3]</td>
			<td style="text-align:center; vertical-align:top">{T3}</td>
			<td style="text-align:left; vertical-align:top">ng/ml</td>
			<td style="text-align:left; vertical-align:top">0.00-1.51 ng/ml</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroxine [T4]</td>
			<td style="text-align:center; vertical-align:top">{T4}</td>
			<td style="text-align:left; vertical-align:top">ug/dl</td>
			<td style="text-align:left; vertical-align:top">4.66-9.32 ug/dl</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Thyroid Stimulating Hormone (TSH)</td>
			<td style="text-align:center; vertical-align:top">{TSH}</td>
			<td style="text-align:left; vertical-align:top">ulU/mL</td>
			<td style="text-align:left; vertical-align:top">0.25-5.00 ulU/mL</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>LIVER FUNCTION TEST (LFT) </strong></p>

<table border="0" cellpadding="3" cellspacing="0" style="width:800px">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td>Bilirubin (Total)</td>
			<td>{SERBIL}</td>
			<td>mg/dl</td>
			<td>0.3-1.2</td>
		</tr>
		<tr>
			<td>Bilirubin (Direct)</td>
			<td>{BD}</td>
			<td>mg/dl</td>
			<td>&lt; 0.3</td>
		</tr>
		<tr>
			<td>Bilirubin (Indirect)</td>
			<td>{BI}</td>
			<td>mg/dl</td>
			<td>&lt; 0.8</td>
		</tr>
		<tr>
			<td>SGOT / Aspartate Aminotransferase (AST)</td>
			<td>{SGOT}</td>
			<td>U/L</td>
			<td>&lt; 35</td>
		</tr>
		<tr>
			<td>SGPT/ Alanine Aminotransferase (ALT)</td>
			<td>{SGPT}</td>
			<td>U/L</td>
			<td>&lt; 40</td>
		</tr>
		<tr>
			<td>Alkaline Phosphatase (Total)</td>
			<td>{SALKALINE}</td>
			<td>U/L</td>
			<td>25-140</td>
		</tr>
		<tr>
			<td>Protein(Total)</td>
			<td>{TOTPRO}</td>
			<td>gm/dl</td>
			<td>6.2-8.0</td>
		</tr>
		<tr>
			<td>Albumin</td>
			<td>{ALBU}</td>
			<td>gm/dl</td>
			<td>3.8-5.4</td>
		</tr>
		<tr>
			<td>Globulin</td>
			<td>{GLOB}</td>
			<td>gm/dl</td>
			<td>1.8-3.6</td>
		</tr>
		<tr>
			<td>A:G Ratio</td>
			<td>{AGR}</td>
			<td>&nbsp;</td>
			<td>1.1-2.0</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>RENAL FUNCTION TEST (KFT)</strong></p>

<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgray"><strong>Test Name</strong></td>
			<td style="background-color:lightgray"><strong>Result</strong></td>
			<td style="background-color:lightgray"><strong>Unit</strong></td>
			<td style="background-color:lightgray"><strong>Bio. Ref. Interval</strong></td>
		</tr>
		<tr>
			<td>Urea</td>
			<td>{BU}&nbsp;</td>
			<td>mg/dL</td>
			<td>11-37</td>
		</tr>
		<tr>
			<td>Creatinine</td>
			<td>{SC}</td>
			<td>mg/dL</td>
			<td>0.6-1.4</td>
		</tr>
		<tr>
			<td>Uric Acid</td>
			<td>{SUA}</td>
			<td>mg/dL</td>
			<td>2.6-7.2</td>
		</tr>
		<tr>
			<td>Calcium</td>
			<td>{SCA}</td>
			<td>mg/dL</td>
			<td>8.6-10.3</td>
		</tr>
		<tr>
			<td>Sodium</td>
			<td>{SS}</td>
			<td>m Mol /L</td>
			<td>135-155</td>
		</tr>
		<tr>
			<td>Potassium&nbsp;</td>
			<td>{SP}</td>
			<td>m Mol /L</td>
			<td>3.5-5.5</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p><strong>Vitamin D</strong></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td>Test Name</td>
			<td>Observed Values</td>
			<td>Unit</td>
			<td>Normal Values</td>
		</tr>
		<tr>
			<td style="text-align:left; vertical-align:top">Vitamin D</td>
			<td style="text-align:center; vertical-align:top">{VITD}</td>
			<td style="text-align:left; vertical-align:top">ng/ml</td>
			<td style="text-align:left; vertical-align:top"><strong>Reference Range:</strong><br />
			<em>Deficent : &lt; 20<br />
			Insufficent : 20-29<br />
			Sufficent : 30-100<br />
			Potential Toxicity &gt; 100</em></td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>

<p>VITAMIN B-12</p>

<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>TEST NAME</strong></td>
			<td><strong>TECHNOLOGY</strong></td>
			<td><strong>VALUE</strong></td>
			<td><strong>UNITS</strong></td>
			<td><strong>NORMAL RANGE</strong></td>
		</tr>
		<tr>
			<td>VITAMIN B-12</td>
			<td>C.L.I.A</td>
			<td>{VITAMIN12}</td>
			<td>pg/ml</td>
			<td>211-911 pg/ml</td>
		</tr>
	</tbody>
</table>
', '15', '1', '1', '3478'),
('134', 'VITAMIN B-12', NULL, '<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td><strong>TEST NAME</strong></td>
			<td><strong>TECHNOLOGY</strong></td>
			<td><strong>VALUE</strong></td>
			<td><strong>UNITS</strong></td>
			<td><strong>NORMAL RANGE</strong></td>
		</tr>
		<tr>
			<td>VITAMIN B-12</td>
			<td>C.L.I.A</td>
			<td>{VITAMIN12}</td>
			<td>pg/ml</td>
			<td>211-911 pg/ml</td>
		</tr>
	</tbody>
</table>
', '15', '1', '1', '163'),
('135', 'D-Dimer', NULL, '<table border="0" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:lightgrey"><strong>Test Name</strong></td>
			<td style="background-color:lightgrey"><strong>Result</strong></td>
			<td style="background-color:lightgrey"><strong>Unit</strong></td>
			<td style="background-color:lightgrey"><strong>Bio.Ref. Interval&nbsp;</strong></td>
			<td style="background-color:lightgrey"><strong>Method</strong></td>
		</tr>
		<tr>
			<td style="vertical-align:top">*D-Dimer<br />
			QUATITATIVE</td>
			<td style="vertical-align:top">{DIMER}</td>
			<td style="vertical-align:top">mg/L</td>
			<td style="vertical-align:top">0.00-0.5</td>
			<td style="vertical-align:top">Turbidimetric Immunoassay</td>
		</tr>
	</tbody>
</table>

<table border="1" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td>
			<p><strong>Comments:</strong></p>

			<ul>
				<li>The formation of D-Dimer requires three hemostatic stages: formation of clot, Factor XIII a cross linking and clot breakdown of Fibrin</li>
				<li>Several studies have shown a correlation of Increased D-dimer levels with clinical conditions that relate to the formation of Fibrin, mirroring an in-vivo lysis of formed cross-linker Fibrin.</li>
				<li>These conditions include Deep Venous Thrombosis , disseminated intra-vascular coagulation, pulmonary Embolism, postoperative states, malignancy, trauma and pre-eclampsia. Signs and symptoms of Deep Venous Thrombosis are non-specific and present in a myriad of non-thrombotic disorders.</li>
				<li>Sample Collected in citrate vial.</li>
				<li>Increased levels have high risk for venons thromboembolism (VTE).</li>
				<li>Lipemia falsely decreases D-dimer levels (intralipid value&gt;250 mg/dl).</li>
			</ul>
			</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '1', '1', '1', '368'),
('136', 'Beta- Human ChorionicGonodotropin Hormone', NULL, '<table border="0" cellpadding="3" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="background-color:#bfbfbf"><strong>Test Name</strong></td>
			<td style="background-color:#bfbfbf; text-align:center"><strong>Result</strong></td>
			<td style="background-color:#bfbfbf"><strong>Unit</strong></td>
			<td style="background-color:#bfbfbf"><strong>Bio.Ref. Interval</strong></td>
			<td style="background-color:#bfbfbf"><strong>Method</strong></td>
		</tr>
		<tr>
			<td>Beta- Human ChorionicGonodotropin Hormone</td>
			<td>{BHCG}</td>
			<td><span dir="ltr" style="font-family:sans-serif; font-size:14.7585px">mIU/mL</span></td>
			<td>(Age/Gender specific)</td>
			<td>CMIA</td>
		</tr>
	</tbody>
</table>

<p><strong>&nbsp;Comments:</strong></p>

<ul>
	<li>Human Chorionic Gonadotropin (hCG) is a glycoprotein with two covalently bound subunits. The alpha subunit is similar to those of Luteinizing Hormone (LH),Follicle Stimulating Hormone (FSH), and Thyroid Stimulating hormone (TSH).</li>
	<li>In pregnancy the levels of hCG increases exponentially for about 8 to 10 weeks after the last menstrual cycle.</li>
	<li>Later in pregnancy about 12 weeks after conception, the concentration of hCG begins to fall as the placenta begins to produce steroid hormones. Other sourcesof elevated hCG values are ectopic pregnancy, threatened abortion, and recent termination of pregnancy.</li>
</ul>

<p><strong>Biological Reference Ranges :&nbsp;</strong></p>

<table align="left" border="1" cellpadding="1" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="text-align:center"><strong>GA in Wks</strong></td>
			<td style="text-align:center"><strong>Reference Range</strong></td>
			<td style="text-align:center"><strong>GA in Wks</strong></td>
			<td style="text-align:center"><strong>Reference Range</strong></td>
			<td style="text-align:center"><strong>GA in Wks</strong></td>
			<td style="text-align:center"><strong>Reference Range</strong></td>
		</tr>
		<tr>
			<td style="text-align:center">1 - 3</td>
			<td style="text-align:center">16 - 4870</td>
			<td style="text-align:center">3 - 4</td>
			<td style="text-align:center">1110 - 31500</td>
			<td style="text-align:center">4-5</td>
			<td style="text-align:center">2560 - 82300</td>
		</tr>
		<tr>
			<td style="text-align:center">5 - 6</td>
			<td style="text-align:center">23100 - 151000</td>
			<td style="text-align:center">6 - 7</td>
			<td style="text-align:center">27300 - 233000</td>
			<td style="text-align:center">7 - 11</td>
			<td style="text-align:center">20900 - 291000</td>
		</tr>
		<tr>
			<td style="text-align:center">11 - 16</td>
			<td style="text-align:center">6140 - 103000</td>
			<td style="text-align:center">16 - 39</td>
			<td style="text-align:center">2700 - 78100</td>
			<td style="text-align:center">&nbsp;</td>
			<td style="text-align:center">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3" rowspan="1" style="text-align:center">Non pregnant Female and Male</td>
			<td style="text-align:center">&lt; 5.3</td>
			<td style="text-align:center">&nbsp;</td>
			<td style="text-align:center">&nbsp;</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
', '13', '1', '1', '407'),
('137', 'COVID -19 Antigen Test', NULL, '<table border="1" cellpadding="1" cellspacing="0" style="height:73px; width:636px">
	<tbody>
		<tr>
			<td><strong>Test Name</strong></td>
			<td><strong>Result</strong></td>
		</tr>
		<tr>
			<td>COVID-19 Antigen<br />
			(Immunochromatogrphy)</td>
			<td>{covid_antigen_test}<br />
			&nbsp;</td>
		</tr>
	</tbody>
</table>

<p><strong>Note :</strong></p>

<ol>
	<li>Test Results must be evalvated in conjunction with other clinical date available to the phusician.</li>
	<li>Negative tests results do not rule out possible other non-covid-19 viral infection.&nbsp;</li>
</ol>
', '15', '1', '1', '3493'),
('138', 'PCOS. PROFILE', NULL, '<p><strong>Test Name&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Result&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Unit&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Bio Ref. Interval</strong></p>

<p><strong>PCOS PANEL</strong></p>

<p><strong>( Hexokinase CLIA,CMIA</strong></p>

<p><strong>Glucose Fasting</strong></p>

<p><strong>FSH</strong></p>

<p><strong>LH</strong></p>

<p><strong>LH,FSH Ratio&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong>&lt; 2.50</p>

<p><strong>Prolactin Serum</strong></p>

<p><strong>Testosterone Total</strong></p>

<p><strong>TSH</strong></p>

<p><strong>Note-</strong></p>

<p><strong>1.Ratio of LH to FSH &gt;2.50 Indicates the presence ofpcos</strong></p>

<p><strong>2.Test conducted on Plasma ( Glucose) and serum ( remainingtests)</strong></p>

<p><strong>3.TSH levels are subject to circadian variation reaching peak levels between 2-4 a.m. and at a</strong></p>

<p><strong>&nbsp; minimum between 6-10 pm. The variation is of the order of 50%.Hence time of the day has influence</strong></p>

<p><strong>&nbsp; on the measured serum TSH concentrations.</strong></p>

<p><strong>4.TSH values &lt;0.03 ulU/mL. need to be clinically correlated due to presence of a rare TSH variant in</strong></p>

<p><strong>some individuals.</strong></p>

<p><strong>Interpretation.</strong></p>

<p>&nbsp;</p>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td><strong>TANNER STAGE</strong></td>
			<td>AGE IN YEARS</td>
			<td>FSH</td>
			<td>LH</td>
			<td>PROLACTIN</td>
			<td>&nbsp;TESTOSTERONE TOTAL</td>
		</tr>
		<tr>
			<td>1.</td>
			<td>&lt; 9.2</td>
			<td>1.00-4.00</td>
			<td>0.02-0.18</td>
			<td>3.6-12.0</td>
			<td>2-10</td>
		</tr>
		<tr>
			<td>2.</td>
			<td>9.2-13.7</td>
			<td>1.00-10.80</td>
			<td>0.02-4.70</td>
			<td>2.6-18.0</td>
			<td>5-30</td>
		</tr>
		<tr>
			<td>3.</td>
			<td>10.0-14.4</td>
			<td>1.50-12.80</td>
			<td>0.10-12.00</td>
			<td>2.60-18.0</td>
			<td>10-30</td>
		</tr>
		<tr>
			<td>4.</td>
			<td>10.7-15.6</td>
			<td>1.50-11.70</td>
			<td>0.40-11.70</td>
			<td>3.2-20.0</td>
			<td>
			<p>15-40</p>
			</td>
		</tr>
		<tr>
			<td>5.</td>
			<td>11.8-18.6</td>
			<td>1.00-9.20</td>
			<td>0.40-11.70</td>
			<td>3.2-20.0</td>
			<td>10-40</td>
		</tr>
	</tbody>
</table>

<p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p>

<p><strong>Comments</strong></p>

<p>polycystic Ovarian Syndrome is a clinically entity associated with enlarged ovaries Infertility hirsutism obesity</p>

<p>and amenorrhoea. PCOS is clinically defined by hyperandrogenism with chronic anovulation in females</p>

<p>without underlying disease of adrenal of pituitary glands. This syndrome is characterized by normal/ low</p>

<p>levels of FSH with elevated levels of LH &amp; testostterone. Females with PCOD have a greater frequency of&nbsp;</p>

<p>hyperinsulinemia and insulin resistance.</p>
', '1', '1', '1', '0'),
('139', 'OGTT', NULL, '<p><strong>TEST NAME&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;RESULT&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;UNIT&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;BIO REF INTERVAL</strong></p>

<p><strong>Fasting Glucose&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;mg/dl&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;60.00-110.00</strong></p>

<p><strong>Blood Glucose&nbsp;( pp) after 75 gm&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;mg/dl&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 80.00-140.00</strong></p>

<p><strong>Glucose&nbsp;</strong></p>
', '1', '1', '1', '0'),
('140', 'Urine for protein creatinine Ratio', NULL, '<p><strong>PROTEIN CREATININE RATIO ,URINE</strong></p>

<p><strong>(Spectrophotometry)</strong></p>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td>Test Name</td>
			<td>Result</td>
			<td>Bio Ref. Interval</td>
		</tr>
		<tr>
			<td>Protein,Total</td>
			<td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;mg/dl</td>
			<td>&lt;14.00</td>
		</tr>
		<tr>
			<td>Creatinine</td>
			<td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;mg/dl</td>
			<td>Link with age</td>
		</tr>
		<tr>
			<td>Protein Creatinine Ratio</td>
			<td>&nbsp;</td>
			<td>&nbsp;&lt;0.20</td>
		</tr>
	</tbody>
</table>

<p><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</strong></p>

<p><strong>Interpretation</strong></p>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">
	<tbody>
		<tr>
			<td>Protein Creatinine Ratio</td>
			<td>Remark</td>
		</tr>
		<tr>
			<td>&lt; 0.20</td>
			<td>Normal</td>
		</tr>
		<tr>
			<td>0.20-1.00</td>
			<td>Low grade Prteinuria</td>
		</tr>
		<tr>
			<td>1.00-5.00</td>
			<td>Moderate Proteinuria</td>
		</tr>
		<tr>
			<td>&gt; 5.00</td>
			<td>Nephrosis</td>
		</tr>
	</tbody>
</table>

<p><strong>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</strong>&nbsp; &nbsp; &nbsp;&nbsp;</p>

<p><strong>Comments</strong></p>

<p>Urinary total proteins are nearly negligible in healthy adults. The Protein Creatinine ratio is a simple</p>

<p>and convenient method to quantitate and monitor proteinuria in adults with chronic kidney disease.</p>

<p>Patients With 2 or more positive results within a period of 1-2 week should be labeled as having</p>

<p>persistent proteinuria and investigated Further.</p>
', '1', '1', '1', '0'),
('141', 'Mantoux test', NULL, '<p>INDURATION MEASURED IS - MM</p>

<p>RESULT INTERPRETATION LESS THAN 5- NEGATIVE</p>

<p>5-9 MM EQUIVOCAL</p>

<p>GREATER THAN 10 MM POSITIVE</p>
', '1', '1', '1', '0'),
('142', 'serum fsh', NULL, '', '13', '1', '1', '234'),
('143', 'LARGE BIOPSY', NULL, '', '11', '1', '1', '0');

