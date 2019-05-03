<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Inspire 305 Voting Marketing Signup Tool</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style type="text/css">
		.container {
			max-width: 720px;
		}
	</style>
</head>

<?php
	if (isset($_POST['submit']) && $_POST['submit'] == 'Submit') {
		$fileTmpPath = $_FILES['upload']['tmp_name'];
		$fileName = $_FILES['upload']['name'];
		$fileSize = $_FILES['upload']['size'];
		$fileType = $_FILES['upload']['type'];
		$fileNameCmps = explode(".", $fileName);
		$fileExtension = strtolower(end($fileNameCmps));

		$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
		$uploadFileDir = './tmp/';
		$dest_path = $uploadFileDir . $newFileName;
		if(move_uploaded_file($fileTmpPath, $dest_path)) {
		  $message ='File is successfully uploaded. Checking for columns.';
		}
		else {
		  $message = 'There was some error moving the file to upload directory.';
		}

		$filename = 'tmp/' . $newFileName;

		$file_array = []; 
		if (($h = fopen("{$filename}", "r")) !== FALSE) {
		  while (($data = fgetcsv($h, 1000, "|")) !== FALSE) {
		    $file_array[] = $data;		
		  }
		  fclose($h);
		}
		$csv_headers = array_shift($file_array);
		for ($i = 0; $i < count($csv_headers); $i++) {
			$csv_headers[$i] = strtolower(str_replace(array('"', ' (required)'), '', preg_replace('/[\x00-\x1F\x7F]/', '', $csv_headers[$i])));
		}

		if (isset($_POST['fromDate'])) {
			$fdate = strtotime($_POST['fromDate']);
		} else {
			$fdate = date();
		}

		$mkt_optin = null;
		$fname = null;
		$lname = null;
		$stime = null;
		$email = null;
		for ($i = 0; $i < count($csv_headers); $i++) {
			$value = $csv_headers[$i];
			//echo '<h3>'.$value.'</h3>';
			if ('mkt_optin' == $value) {
				$mkt_optin = $i;
			}
			if ('first name' == $value) {
				$fname = $i;
			}
			if ('last name' == $value) {
				$lname = $i;
			}
			if ('email address' == $value) {
				$email = $i;
			}
			if ('time' == $value) {
				$stime = $i;
			}
		}

		if (null !== $mkt_optin && null !== $fname && null !== $lname && null !== $email && null !== $fdate) {
			$message = 'Columns found. Scanning for optins.';
			$emailDups = array();
			$email_optins = array();
			for ($i = 0; $i < count($file_array); $i++) {
				$row = $file_array[$i];
				$submitTime = strtotime(str_replace('"', '', preg_replace('/[\x00-\x1F\x7F]/', '', $row[$stime])));
				if ('array' == strtolower(preg_replace('/[\x00-\x1F\x7F]/', '', $row[$mkt_optin])) && $submitTime >= $fdate) {
					$newRow = array(
						trim(str_replace('"', '', preg_replace('/[\x00-\x1F\x7F]/', '', $row[$fname]))),
						trim(str_replace('"', '', preg_replace('/[\x00-\x1F\x7F]/', '', $row[$lname]))),
						trim(str_replace('"', '', preg_replace('/[\x00-\x1F\x7F]/', '', $row[$email])))
					);
					if (!in_array($row[$email], $emailDups) && strpos($row[$email], '@')) {
						array_push($email_optins, $newRow);
						array_push($emailDups, $row[$email]);
					}
				}
				$message = 'List generated. Saving as CSV file.';
			}

			$timestamp = date('Y-m-d_his');
			$fp_name = 'results/inspire305-vote-signups_' . $timestamp . '.csv';
			$fp = fopen($fp_name, 'wb');
			$fp_headers = array('First Name', 'Last Name', 'Email Address');
			fputcsv($fp, $fp_headers);
			foreach ($email_optins as $row) {
				fputcsv($fp, $row);
			}
			fclose($fp);
			$message = 'Email List Signups CSV generated. Click the link below to download if it does not download automatically.';
			$downloadLink = '<a id="download" href="./'.$fp_name.'">Download Email Signups</a>';

			unlink($dest_path);

		} else {
			$message = 'There was an error finding columns in this spreadsheet.';
		}
	} else {
		$message = 'Please upload your spreadsheet.';
	}
?>


<body>
	<main class="container mt-4 mb-4">
		<header>
			<h1 class="display-4">Inspire 305 Marketing Signup Tool</h1>
			<p class="lead">
				<?php echo $message; ?>
			</p>
		</header>
		<?php
			if (isset($downloadLink)) {
				echo '<p>'.$downloadLink.'</p>';
			}
			if (is_array($email_optins) && !empty($email_optins)) {
				echo '<table class="table thead-dark table-striped table-bordered table-sm"><tbody>';
				echo '<caption style="caption-side: top">Email Signups since '. date('F j, Y', $fdate) .'</caption>';
				echo '<tr>';
				foreach ($fp_headers as $fp_header) {
					echo '<th scope="col">'.$fp_header.'</th>';
				}
				echo '</tr>';
				foreach ($email_optins as $row) {
					echo '<tr>';
					foreach ($row as $cell) {
						echo '<td>'.$cell.'</td>';
					}
					echo '</tr>';
				}
				echo '</tbody><table>';
				echo '<br><hr><br>';
			}
		?>
		<form action="/inspire_email_signups/index.php" method="post" style="" enctype="multipart/form-data">
			<div class="row">
				<div class="col-sm-8">
					<fieldset class="form-group">
						<label for="upload">
							Upload document:
							<span class="text-danger">*</span>
						</label>
						<input class="form-control-file" type="file" name="upload" accept="text/csv" required>
						<small class="form-text text-muted">Only pipe-delimited "|" .csv files accepted.</small>
					</fieldset>
				</div>
				<div class="col-sm-4">
					<fieldset class="form-group">
						<label for="fromDate">
							Starting Date
							<span class="text-danger">*</span>
						</label>
						<input class="form-control" type="text" name="fromDate" placeholder="YYYY-MM-DD" required>
						<small class="form-text text-muted">Please enter dates as Year, Month, Day like YYYY-MM-DD.</small>
					</fieldset>
				</div>
			</div>
			<fieldset>
				<input class="btn btn-primary text-right" type="submit" name="submit" value="Submit">
			</fieldset>
		</form>
		<footer class="mt-4">
			<hr>
			<p class="text-muted small text-center">Developed by Kris Williams/Roar Media exclusively for use by Inspire305. All Rights Reserved.</p>
		</footer>
	</main>
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			if ($('#download').length) {
				window.location.href = $('#download').attr('href');
			}
		});
	</script>
</body>
</html>