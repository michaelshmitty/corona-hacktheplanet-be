<?php
  // Get the contents of the JSON file
  $strJsonFileContents = file_get_contents("case_distribution.json");

  // Convert to array
  $data = json_decode($strJsonFileContents, true);

  // Get list of countries and territories
  $countries_and_territories = array();
  foreach ($data['records'] as $record) {
    $countries_and_territories[$record['geoId']] = $record['countriesAndTerritories'];
  }

  // Filter cases based on country
  $country_code = $_GET['country'];
  if (empty($country_code)) {
    $country_code = 'BE';
  }
  $cases = array_filter($data['records'], function($record) {
    global $country_code;
    return $record['geoId'] == $country_code;
  });

  // Skip today's results as they may be incomplete
  $today = array_shift($cases);

  $country = str_replace('_', ' ', $today['countriesAndTerritories']);

  $cases_chronological = array_reverse($cases);
  $cases_last_14_days = array_reverse(array_slice($cases, 0, 14));
?>

<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8'>
    <meta content='IE=Edge,chrome=1' http-equiv='X-UA-Compatible'>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no' name='viewport'>
    <meta content='yes' name='apple-mobile-web-app-capable'>
    <title>COVID-19 cases <?php echo $country ?></title>
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="/css/normalize.css">
    <link rel="stylesheet" href="/css/skeleton.css">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js" integrity="sha512-s+xg36jbIujB2S2VKfpGmlC3T5V2TF3lY48DX7u2r9XzGzgPsa6wTpOQA7J9iffvdeBN0q9tKzRxVxw1JviZPg==" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="eight columns">
          <h4>
            COVID-19 cases in <?php echo $country ?>
          </h4>
          <p>
            <small>
              Source:
              <a href="https://www.ecdc.europa.eu/en" target="_blank">
                European Centre for Disease Prevention and Control (ECDC)
              </a>.
            </small>
            <br>
            <small>
              Data processed and presented by
              <a href="https://michaelsmith.be" target="_blank">
                Michael Smith
              </a>.
            </small>
          </p>
        </div>
        <div class="four columns">
          <label for="country">Select a country:</label>
          <select name="country" id="country-select">
            <?php
              foreach ($countries_and_territories as $key => $value) {
                $value = substr($value, 0, 25);
                $value = str_replace('_', ' ', $value);
                if ($key == $country_code) {
                  echo "<option value='{$key}' selected>{$value}</option>";
                } else {
                  echo "<option value='{$key}'>{$value}</option>";
                }
              }
            ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="twelve columns">
          <p>
            <canvas id="chart3" width="900" height="600"></canvas>
          </p>
        </div>
      </div>
      <div class="row">
        <div class="twelve columns">
          <p>
            <canvas id="chart2" width="900" height="600"></canvas>
          </p>
        </div>
      </div>
      <div class="row">
        <div class="twelve columns">
          <p>
            <canvas id="chart1" width="900" height="600"></canvas>
          </p>
        </div>
      </div>
      <div class="row">
        <div class="twelve columns">
          <table class="u-full-width">
            <thead>
              <tr>
                <th>Date</th>
                <th>Cases</th>
                <th>Deaths</th>
                <th>Cumulative</th>
              </tr>
            </thead>
            <tbody>
              <?php
                foreach ($cases as $record) {
                  $date = $record['dateRep'];
                  $amount = $record['cases'];
                  $deaths = $record['deaths'];
                  $cumulative = round($record['Cumulative_number_for_14_days_of_COVID-19_cases_per_100000']);
                  echo "<tr><td>{$date}</td><td>{$amount}</td><td>{$deaths}</td><td>{$cumulative}</td></tr>";
                }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script>
      window.addEventListener('DOMContentLoaded', (event) => {
        var countrySelect = document.getElementById('country-select');
        countrySelect.addEventListener('change', (event) => {
          window.location='/?country=' + event.target.value;
        });
      });
    </script>

    <script>
      var ctx = document.getElementById('chart1');
      var config1 = {
        type: 'line',
        data: {
          labels: [<?php
                foreach ($cases_chronological as $record) {
                  echo "'{$record['dateRep']}', ";
                }
              ?>],
          datasets: [
            {
              label: 'Cases',
              backgroundColor: 'rgb(54, 162, 235)',
              borderColor: 'rgb(54, 162, 235)',
              data: [
                <?php
                  foreach ($cases_chronological as $record) {
                    echo "'{$record['cases']}',";
                  }
                ?>
              ],
              fill: false,
            },
            {
              label: 'Deaths',
              backgroundColor: 'rgb(255, 0, 0)',
              borderColor: 'rgb(255, 0, 0)',
              data: [
                <?php
                  foreach ($cases_chronological as $record) {
                    echo "'{$record['deaths']}',";
                  }
                ?>
              ],
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          title: {
            display: true,
            text: 'All COVID-19 cases in <?php echo $country ?>',
          },
          tooltips: {
            mode: 'index',
            intersect: false,
          },
          hover: {
            mode: 'nearest',
            intersect: true,
          },
          scales: {
            xAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Day',
                },
              },
            ],
            yAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Count',
                },
              },
            ],
          },
        },
      };

      var chart1 = new Chart(ctx, config1);
    </script>

    <script>
      var ctx = document.getElementById('chart2');
      var config2 = {
        type: 'line',
        data: {
          labels: [<?php
                foreach ($cases_last_14_days as $record) {
                  echo "'{$record['dateRep']}', ";
                }
              ?>],
          datasets: [
            {
              label: 'Cases',
              backgroundColor: 'rgb(54, 162, 235)',
              borderColor: 'rgb(54, 162, 235)',
              data: [
                <?php
                  foreach ($cases_last_14_days as $record) {
                    echo "'{$record['cases']}',";
                  }
                ?>
              ],
              fill: false,
            },
            {
              label: 'Deaths',
              backgroundColor: 'rgb(255, 0, 0)',
              borderColor: 'rgb(255, 0, 0)',
              data: [
                <?php
                  foreach ($cases_last_14_days as $record) {
                    echo "'{$record['deaths']}',";
                  }
                ?>
              ],
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          title: {
            display: true,
            text: 'COVID-19 cases in <?php echo $country ?> in the past 14 days',
          },
          tooltips: {
            mode: 'index',
            intersect: false,
          },
          hover: {
            mode: 'nearest',
            intersect: true,
          },
          scales: {
            xAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Day',
                },
              },
            ],
            yAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Count',
                },
              },
            ],
          },
        },
      };

      var chart2 = new Chart(ctx, config2);
    </script>

    <script>
      var ctx = document.getElementById('chart3');
      var config3 = {
        type: 'line',
        data: {
          labels: [<?php
                foreach ($cases_chronological as $record) {
                  echo "'{$record['dateRep']}', ";
                }
              ?>],
          datasets: [
            {
              label: 'Cases',
              backgroundColor: 'rgb(54, 162, 235)',
              borderColor: 'rgb(54, 162, 235)',
              data: [
                <?php
                  foreach ($cases_chronological as $record) {
                    $cumulative = round($record['Cumulative_number_for_14_days_of_COVID-19_cases_per_100000']);
                    echo "'{$cumulative}',";
                  }
                ?>
              ],
              fill: false,
            }
          ],
        },
        options: {
          responsive: true,
          title: {
            display: true,
            text: '14-day cumulative number of COVID-19 cases per 100 000',
          },
          tooltips: {
            mode: 'index',
            intersect: false,
          },
          hover: {
            mode: 'nearest',
            intersect: true,
          },
          scales: {
            xAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Day',
                },
              },
            ],
            yAxes: [
              {
                display: true,
                scaleLabel: {
                  display: false,
                  labelString: 'Count',
                },
              },
            ],
          },
        },
      };

      var chart3 = new Chart(ctx, config3);
    </script>
  </body>
</html>

