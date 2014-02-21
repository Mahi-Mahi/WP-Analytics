<div class="metabox-holder">
	<div class="">

		<p>
			total views :
			<?php
			print wp_analytics_get($_GET['post']);
			?>
		</p>

		<p>
			<?php
			$values = wp_analytics_gets('day', $_GET['post']);
			$json_values = array(array('Date', 'PageViews'));
			foreach($values as $row):
				$json_values[] = array($row->period, (int)$row->count_value);
			endforeach;
			?>

			<div id="wp_analytics_chart_div"></div>

			<script type="text/javascript">
				google.load("visualization", "1", {packages:["corechart"]});
				google.setOnLoadCallback(drawChart);

				function drawChart() {
					/*
					[
						['Date', 'PageViews'],
						['2004',  1000],
						['2005',  1170],
						['2006',  660],
						['2007',  1030]
					]
					*/
					var data = google.visualization.arrayToDataTable(<?php print json_encode($json_values) ?>);

					var chart = new google.visualization.LineChart(document.getElementById('wp_analytics_chart_div'));
					chart.draw(data, {
						title: ''
					});
				}
			</script>
		</p>

	</div>
</div>