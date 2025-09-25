<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Survey List']">

      </breadcrumb>

      <div class="row">
        <div class="col-xl-12">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex">
                    <div class="flex-grow-1">
                      <div class="row">
                        <div class="col-md-2">
                          <div class="form-group">
                            <datepicker v-model="from_date" :format="customFormatter" placeholder="Enter From Date" input-class="form-control"></datepicker>
                          </div>
                        </div>
                        <div class="col-md-2">
                          <div class="form-group">
                            <datepicker v-model="to_date" :format="customFormatter" placeholder="Enter To Date" input-class="form-control"></datepicker>
                          </div>
                        </div>
                        <div class="col-md-2">
                          <div class="form-group">
                            <select name="teacher_id" id="teacher_id" v-model="teacher_id" class="form-control">
                              <option disabled value="">Select Teacher</option>
                              <option :value="teacher.user_id" v-for="(teacher , index) in teachers" :key="index">{{ teacher.name }}</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-2">
                          <button type="submit" @click="surveyList" class="btn btn-success"><i class="mdi mdi-filter"></i>Filter</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-bordered table-striped dt-responsive nowrap dataTable no-footer dtr-inline table-sm small">
                      <thead>
                      <tr>
                        <th v-for="(header, index) in tableHeaders" :key="index">
                          {{ formatHeader(header) }}
                        </th>
                        <th>Actions</th>
                      </tr>

                      </thead>
                      <tbody>
                      <tr v-for="(row, rowIndex) in tableData" :key="rowIndex">
                        <td v-for="(header, colIndex) in tableHeaders" :key="colIndex">
                          {{ row[header] }}
                        </td>
                        <td>
                          <button class="btn btn-sm btn-primary" @click="viewRow(row.evaluation_id)">View</button>
                        </td>
                      </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="row">
                    <div class="col-4">
                      <div class="data-count">
                        Show {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} rows
                      </div>
                    </div>
                    <div class="col-8">
                      <pagination
                          v-if="pagination.last_page > 1"
                          :pagination="pagination"
                          :offset="5"
                          @paginate="query === '' ? surveyList() : searchData()"
                      ></pagination>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--  Modal content for the above example -->
      <div class="modal fade" id="StudentModelModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title mt-0" id="myLargeModalLabel"> Details</h5>
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true" @click="closeModal">×</button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped dt-responsive nowrap dataTable no-footer dtr-inline table-sm small">
                <thead>
                <tr>
                  <th>SN</th>
                  <th>Statement</th>
                  <th>Rating Got</th>
                  <th>Total rating</th>
                </tr>
                </thead>
                <tbody>
                  <tr v-for="(detail, i) in details" :key="detail.id" v-if="details.length">
                    <th scope="row">{{ ++i }}</th>
                    <td>{{ detail.question }}</td>
                    <td>{{ detail.answer }}</td>
                    <td>{{ detail.total_rating }}</td>
                  </tr>
                  <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td><strong>{{ ratingSum }}</strong></td>
                    <td><strong>{{ ratingSumTotal }}</strong></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

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
      tableHeaders: [],
      tableData: [],
      sessions: [],
      teachers: [],
      details: [],
      pagination: {
        current_page: 1,
        from: 1,
        to: 1,
        total: 1,
        last_page: 1,
      },
      query: "",
      isLoading: false,
      from_date: '',
      to_date: '',
      teacher_id: '',
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.surveyList();
      } else {
        this.searchData();
      }
    }
  },
  computed: {
    ratingSum() {
      return this.details
          .filter(q => q.question_type === 'rating')
          .reduce((sum, q) => sum + Number(q.answer), 0);
    },
    ratingSumTotal() {
      return this.details
          .filter(q => q.question_type === 'rating')
          .reduce((sum, q) => sum + Number(q.total_rating), 0);
    }
  },
  mounted() {
    document.title = 'Survey List | Survey';
    this.surveyList();
    this.getAllTeacher();
  },
  methods: {
    surveyList(){
      let fromdate =  this.from_date ? moment(this.from_date).format('YYYY-MM-DD') : '';
      let todate =  this.to_date ? moment(this.to_date).format('YYYY-MM-DD') : '';
      axios.get('/api/survey-list?page='+ this.pagination.current_page
          + "&from_date="+ fromdate
          + "&to_date=" + todate
          + "&teacher_id=" + this.teacher_id
          + "&query=" + this.query
      ).then((response)=>{
          this.tableData = response.data.surveyList.data;
          if (this.tableData.length > 0) {
            this.tableHeaders = Object.keys(this.tableData[0]);
          }
            this.pagination.current_page = response.data.surveyList.current_page;
            this.pagination.from = response.data.surveyList.from;
            this.pagination.to = response.data.surveyList.to;
            this.pagination.total = response.data.surveyList.total;
            this.pagination.last_page = response.data.surveyList.last_page;
          }).catch((error)=>{

      })
    },
    searchData(){
      axios.get(baseurl+"api/search/student/" + this.query + "?page=" + this.pagination.current_page).then(response => {
        this.students = response.data.data;
        this.pagination = response.data.meta;
      }).catch(e => {
        this.isLoading = false;
      });
    },
    getAllTeacher(){
      axios.get(baseurl+'api/get-all-teacher').then((response)=>{
        this.teachers = response.data.teachers;
      }).catch((error)=>{

      })
    },
    viewRow(evaluationId){
      axios.get(baseurl + 'api/get-teachers-evaluation-details?evaluationId=' + evaluationId).then((response)=>{
        console.log(response.data.details)
        this.details = response.data.details;
        $("#StudentModelModal").modal("show");
      }).catch((error)=>{

      })
    },
    closeModal(){
      $("#StudentModelModal").modal("hide");
    },
    exportTeacherWiseAverageRating(){
      let fromdate =  this.from_date ? moment(this.from_date).format('YYYY-MM-DD') : '';
      let todate =  this.to_date ? moment(this.to_date).format('YYYY-MM-DD') : '';

      axios.get(baseurl + 'api/question-wise-analysis?from_date='+ fromdate
          + "&to_date=" + todate
      ).then((response)=>{
        let dataSets = response.data.data;
        if (dataSets.length > 0) {
          let columns = Object.keys(dataSets[0]);
          columns = columns.filter((item) => item !== 'row_num');
          let rex = /([A-Z])([A-Z])([a-z])|([a-z])([A-Z])/g;
          columns = columns.map((item) => {
            let title = item.replace(rex, '$1$4 $2$3$5')
            return {title, key: item}
          });
          bus.$emit('data-table-import', dataSets, columns, 'exportTeacherWiseAverageRating')
        }
      }).catch((error)=>{
      })
    },
    customFormatter(date) {
      return moment(date).format('YYYY-MM-DD');
    },
    formatHeader(header) {
      return header
          .replace(/_/g, " ")
          .replace(/\b\w/g, c => c.toUpperCase());
    }
  },
}
</script>

<style scoped>

</style>
