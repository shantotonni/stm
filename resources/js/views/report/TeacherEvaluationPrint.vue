<template>
  <div class="content">
    <div class="print-container" id="print-area">
      <!-- Teacher Info -->
      <div class="teacher-info">
        <h2 class="text-center">{{ teacher.name }}</h2>
        <div class="col-md-12 first_part" >
          <div style="display:flex;">
            <div class="col-md-7">
              <p style="font-size: 18px;">BMDC_NO <span style="margin-left: 70px">: {{teacher.BMDC_NO}}</span></p>
            </div>
            <div class="col-md-5">
              <p style="font-size: 18px;">Email : {{teacher.email}}</p>
            </div>
          </div>
          <div style="display:flex;">
            <div class="col-md-7">
              <p style="font-size: 18px;">Mobile <span style="margin-left: 168px">: {{teacher.mobile}}</span></p>
            </div>
            <div class="col-md-5">
              <p style="font-size: 18px;" v-if="teacher.department">Department : {{teacher.department.name}}</p>
            </div>
          </div>
          <div style="display:flex;">
            <div class="col-md-5">
              <p style="font-size: 18px;" v-if="teacher.designation">Designation : {{teacher.designation.name}}</p>
            </div>
          </div>
        </div>

      </div>

      <!-- Question-wise Average Table -->
      <table class="rating-table">
        <thead>
        <tr>
          <th>Statement</th>
          <th>Rating Got</th>
          <th>Total Rating</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(detail, i) in teacher_evaluation" :key="detail.evaluation_statement_id" v-if="teacher_evaluation.length">
          <th scope="row">{{ ++i }}</th>
          <td>{{ detail.statement }}</td>
          <td v-if="detail.question_type === 'boolean'">
            Yes: {{ detail.yes_count }} | No: {{ detail.no_count }}
          </td>
          <td v-else>
            {{ detail.answer }}
          </td>
          <td v-if="detail.question_type !== 'boolean'">
            {{ detail.total_rating }}
          </td>
          <td v-else>-</td>
        </tr>
        <tr v-if="teacher_evaluation.some(d => d.question_type !== 'boolean')">
          <td colspan="2"><strong>Total</strong></td>
          <td><strong>{{ ratingSum }}</strong></td>
          <td><strong>{{ ratingSumTotal }}</strong></td>
        </tr>


<!--        <tr v-for="q in teacher_evaluation" :key="q.evaluation_statement_id">-->
<!--          <td class="text-left">{{ q.statement }}</td>-->
<!--          <td>{{ q.answer }}</td>-->
<!--          <td>{{ q.total_rating }}</td>-->
<!--        </tr>-->
<!--        <tr>-->
<!--          <td colspan="1"><strong>Total</strong></td>-->
<!--          <td><strong>{{ ratingSum }}</strong></td>-->
<!--          <td><strong>{{ ratingSumTotal }}</strong></td>-->
<!--        </tr>-->

        </tbody>
      </table>
    </div>
    <data-export/>
  </div>
</template>

<script>
import {baseurl} from '../../base_url'
import Datepicker from 'vuejs-datepicker';
import moment from "moment";
import {bus} from "../../app";
export default {
  name: "List",
  components: {
    Datepicker
  },
  data() {
    return {
      teacher_evaluation: [],
      teacher: [],
    }
  },
  mounted() {
    document.title = 'Evaluation Print | Bill';
  },
  computed: {
    ratingSum() {
      const sum = this.teacher_evaluation
          .filter(q => q.question_type === 'rating')
          .reduce((total, q) => total + Number(q.answer), 0);
      return Math.round(sum);
    },
    ratingSumTotal() {
      return this.teacher_evaluation
          .filter(q => q.question_type === 'rating')
          .reduce((sum, q) => sum + Number(q.total_rating), 0);
    }
  },
  created() {
    axios.get(baseurl + `api/get-single-teachers-print/${this.$route.params.teacher_id}`).then((response)=>{
      this.teacher_evaluation = response.data.details
      this.teacher = response.data.teacher
      console.log(response)
      setTimeout(function(){
        window.print()
      },2000)
    });
  },
  methods: {
   //
  },
}
</script>

<style scoped>
.print-container {
  width: 80%;
  margin: 0 auto;
  font-family: Arial, sans-serif;
}

.teacher-info {
  text-align: left;
  margin-bottom: 20px;
  border-bottom: 2px solid #333;
  padding-bottom: 10px;
}

.rating-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

.rating-table th, .rating-table td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}

.rating-table th {
  background-color: #f2f2f2;
  font-weight: bold;
}

.print-btn {
  text-align: center;
  margin-top: 20px;
}

@media print {
  .print-btn {
    display: none;
  }
  body {
    background: white;
  }
}
</style>
