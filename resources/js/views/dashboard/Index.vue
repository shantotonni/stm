<template>
  <div class="content">
    <div class="container-fluid">
      <div class="page-title-box">
        <div class="row align-items-center">
          <div class="col-sm-6">
            <h4 class="page-title">Dashboard</h4>
            <ol class="breadcrumb">
              <li class="breadcrumb-item active">Welcome to Survey Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
      <!-- end row -->
      <div class="row">
        <div class="col-xl-3 col-md-6">
          <div class="card mini-stat bg-primary text-white">
            <div class="card-body">
              <div class="mb-4">
                <div class="float-left mini-stat-img mr-4"><img src="assets/images/services-icon/01.png" alt="" /></div>
                <h5 class="font-16 text-uppercase mt-0 text-white-50">Total Student</h5>
                <h4 class="font-500">{{data.totalStudents}} <i class="mdi mdi-arrow-up text-success ml-2"></i></h4>
              </div>
              <div class="pt-2">
                <div class="float-right">
                  <a href="#" class="text-white-50"><i class="mdi mdi-arrow-right h5"></i></a>
                </div>
<!--                <p class="text-white-50 mb-0">Since last month</p>-->
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card mini-stat bg-primary text-white">
            <div class="card-body">
              <div class="mb-4">
                <div class="float-left mini-stat-img mr-4"><img src="assets/images/services-icon/02.png" alt="" /></div>
                <h5 class="font-16 text-uppercase mt-0 text-white-50">Total Pending Surveys</h5>
                <h4 class="font-500">{{data.pendingSurveys}} <i class="mdi mdi-arrow-down text-danger ml-2"></i></h4>
              </div>
              <div class="pt-2">
                <div class="float-right">
                  <a href="#" class="text-white-50"><i class="mdi mdi-arrow-right h5"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card mini-stat bg-primary text-white">
            <div class="card-body">
              <div class="mb-4">
                <div class="float-left mini-stat-img mr-4"><img src="assets/images/services-icon/03.png" alt="" /></div>
                <h5 class="font-16 text-uppercase mt-0 text-white-50">Total Surveys</h5>
                <h4 class="font-500">{{data.totalSurveys}}<i class="mdi mdi-arrow-up text-success ml-2"></i></h4>
              </div>
              <div class="pt-2">
                <div class="float-right">
                  <a href="#" class="text-white-50"><i class="mdi mdi-arrow-right h5"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card mini-stat bg-primary text-white">
            <div class="card-body">
              <div class="mb-4">
                <div class="float-left mini-stat-img mr-4"><img src="assets/images/services-icon/04.png" alt="" /></div>
                <h5 class="font-16 text-uppercase mt-0 text-white-50">Total Teachers</h5>
                <h4 class="font-500">{{data.totalTeachers}} <i class="mdi mdi-arrow-up text-success ml-2"></i></h4>
              </div>
              <div class="pt-2">
                <div class="float-right">
                  <a href="#" class="text-white-50"><i class="mdi mdi-arrow-right h5"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<!--      <div class="row">-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Top 5 Teacher</h4>-->
<!--              <div class="table-responsive">-->
<!--                <table class="table table-hover table-sm small">-->
<!--                  <thead>-->
<!--                  <tr>-->
<!--                    <th v-for="(header, index) in topTableHeaders" :key="index">-->
<!--                      {{ formatHeader(header) }}-->
<!--                    </th>-->
<!--                  </tr>-->
<!--                  </thead>-->
<!--                  <tbody>-->
<!--                  <tr v-for="(row, rowIndex) in topTableData" :key="rowIndex">-->
<!--                    <td v-for="(header, colIndex) in topTableHeaders" :key="colIndex">-->
<!--                      {{ row[header] }}-->
<!--                    </td>-->
<!--                  </tr>-->
<!--                  </tbody>-->
<!--                </table>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Lowest 5 Teachers</h4>-->
<!--              <div class="table-responsive">-->
<!--                <table class="table table-hover table-sm small">-->
<!--                  <thead>-->
<!--                  <tr>-->
<!--                    <th v-for="(header, index) in lowestTableHeaders" :key="index">-->
<!--                      {{ formatHeader(header) }}-->
<!--                    </th>-->
<!--                  </tr>-->
<!--                  </thead>-->
<!--                  <tbody>-->
<!--                  <tr v-for="(row, rowIndex) in lowestTableData" :key="rowIndex">-->
<!--                    <td v-for="(header, colIndex) in lowestTableHeaders" :key="colIndex">-->
<!--                      {{ row[header] }}-->
<!--                    </td>-->
<!--                  </tr>-->
<!--                  </tbody>-->
<!--                </table>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--      <div class="row">-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Student Participation</h4>-->
<!--              <div>-->
<!--                <canvas id="studentParticipationChart"></canvas>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Teacher Wise Average Rating</h4>-->
<!--              <div>-->
<!--                <canvas id="teacherRatingChart"></canvas>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Phase Wise Survey Submission</h4>-->
<!--              <div>-->
<!--                <canvas id="phaseSurveyChart"></canvas>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Monthly Survey Trend</h4>-->
<!--              <div>-->
<!--                <canvas id="monthlySurveyChart"></canvas>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--        <div class="col-xl-6">-->
<!--          <div class="card">-->
<!--            <div class="card-body">-->
<!--              <h4 class="mt-0 header-title mb-4">Rating Distribution</h4>-->
<!--              <div>-->
<!--                <canvas id="ratingDistributionChart"></canvas>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
    </div>
    <!-- container-fluid -->
  </div>
</template>
<script>
import {Common} from "../../mixins/common";
import Chart from "chart.js";

export default {
  mixins: [Common],
  data() {
    return {
      data: {},
      topTableHeaders: [],
      topTableData: [],
      lowestTableHeaders: [],
      lowestTableData: [],
      stats: null,
      isLoading: true
    }
  },
  created() {
     //
  },
  mounted() {
    document.title = 'Dashboard | Survey';
    this.getData();
    this.topFiveTeacher();
    this.lowestFiveTeacher();
    this.fetchStats();
  },
  methods: {
    getData() {
      this.axiosGet('dashboard-data', (response) => {
        this.data = response
      this.isLoading = false;
      }, (error) => {
        this.errorNoti(error);
      });
    },
    topFiveTeacher(){
      this.axiosGet('top-five-teacher', (response) => {
        this.topTableData = response.topTeachers;
        if (this.topTableData.length > 0) {
          this.topTableHeaders = Object.keys(this.topTableData[0]);
        }
        this.isLoading = false;
      }, (error) => {
        this.errorNoti(error);
      });
    },
    lowestFiveTeacher(){
      this.axiosGet('lowest-five-teacher', (response) => {
        this.lowestTableData = response.lowTeachers;
        if (this.lowestTableData.length > 0) {
          this.lowestTableHeaders = Object.keys(this.lowestTableData[0]);
        }
        this.isLoading = false;
      }, (error) => {
        this.errorNoti(error);
      });
    },
    // chartData(){
    //   this.axiosGet('get-chart-data', (response) => {
    //     this.chartData = response.chartData;
    //     this.isLoading = false;
    //   }, (error) => {
    //     this.errorNoti(error);
    //   });
    // },
    async fetchStats() {
      try {
        const res = await axios.get("api/get-chart-data");
        console.log(res)
        this.stats = res.data;
        this.renderCharts();
      } catch (err) {
        console.error("Error fetching stats:", err);
      }
    },
    renderCharts() {
      // 1️⃣ Student Participation (Pie)
      new Chart(document.getElementById("studentParticipationChart"), {
        type: "pie",
        data: {
          labels: ["Submitted", "Pending"],
          datasets: [
            {
              data: [
                this.stats.studentParticipation.submitted,
                this.stats.studentParticipation.pending,
              ],
            },
          ],
        },
      });

      // 2️⃣ Teacher Wise Rating (Bar)
      new Chart(document.getElementById("teacherRatingChart"), {
        type: "bar",
        data: {
          labels: this.stats.teacherWiseRating.map((t) => t.teacher_name),
          datasets: [
            {
              label: "Average Rating",
              data: this.stats.teacherWiseRating.map((t) => t.avg_score),
            },
          ],
        },
        options: {
          scales: {
            yAxes: [
              {
                ticks: {
                  min: 1,
                  max: 5,
                  stepSize: 1,
                },
              },
            ],
          },
        },
      });

      // 3️⃣ Phase Wise Survey (Doughnut)
      new Chart(document.getElementById("phaseSurveyChart"), {
        type: "doughnut",
        data: {
          labels: this.stats.phaseWiseSurvey.map((p) => p.student_phase),
          datasets: [
            {
              data: this.stats.phaseWiseSurvey.map((p) => p.total_surveys),
            },
          ],
        },
      });

      // 4️⃣ Monthly Trend (Line)
      new Chart(document.getElementById("monthlySurveyChart"), {
        type: "line",
        data: {
          labels: this.stats.monthlySurveys.map((m) => m.month),
          datasets: [
            {
              label: "Surveys",
              data: this.stats.monthlySurveys.map((m) => m.total_surveys),
              fill: false,
              borderColor: "blue",
            },
          ],
        },
      });

      // 5️⃣ Rating Distribution (Bar)
      new Chart(document.getElementById("ratingDistributionChart"), {
        type: "bar",
        data: {
          labels: this.stats.ratingDistribution.map((r) => r.rating),
          datasets: [
            {
              label: "Count",
              data: this.stats.ratingDistribution.map((r) => r.total),
            },
          ],
        },
      });
    },
    formatHeader(header) {
      return header
          .replace(/_/g, " ")
          .replace(/\b\w/g, c => c.toUpperCase());
    }
  }
}
</script>


<style scoped>
.bg-blue {
  background: #626ed4!important;
  text-align: center;
  text-transform: uppercase;
}
.card-body {
  padding: 0.60rem !important;
}
.bg-blue span {
  font-size: 18px;
}
.task {
  background: #ff8c5e42;
  padding: 5px 8px;
  font-size: 14px;
  margin-bottom: 10px;
  border-radius: 5px;
  font-weight: bold;
}
.links {
  /*height: 148px;*/
}
.helpline {
  height: 120px;
  text-align: center;
  position: relative;
  background-image: linear-gradient(180deg,#ff8c5e8c,#ff8c5e47);
}
.helpline span {
  position: absolute;
  top: 40%;
  left: 0;
  right: 0;
  font-weight: bold;
  font-size: 20px;
  text-transform: uppercase;
  color: #2233c4;
}
.contact {
  height: 120px;
  text-align: center;
  padding-top: 20px;
  background-image: linear-gradient(180deg,#ff8c5e8c,#ff8c5e47);
}
.contact p {
  margin: 0;
  padding: 2px;
  font-weight: bold;
}
.header-bg {
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  text-transform: uppercase;
}
</style>
