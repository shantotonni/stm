<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Teacher wise rating']"></breadcrumb>

      <div class="row">
        <div class="col-xl-12">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex">
                    <div class="flex-grow-1">
                      <div class="row">
<!--                        <div class="col-md-2">-->
<!--                          <div class="form-group">-->
<!--                            <select name="sessionId" id="sessionId" v-model="sessionId" class="form-control">-->
<!--                              <option disabled value="">Select Session</option>-->
<!--                              <option :value="session.session_id" v-for="(session , index) in sessions" :key="index">{{ session.name }}</option>-->
<!--                            </select>-->
<!--                          </div>-->
<!--                        </div>-->
<!--                        <div class="col-md-2">-->
<!--                          <div class="form-group">-->
<!--                            <select name="categoryId" id="categoryId" v-model="categoryId" class="form-control">-->
<!--                              <option disabled value="">Select Category</option>-->
<!--                              <option :value="category.id" v-for="(category , index) in categories" :key="index">{{ category.name }}</option>-->
<!--                            </select>-->
<!--                          </div>-->
<!--                        </div>-->
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
                          <button type="submit" @click="teacherWiseAverageRating" class="btn btn-success"><i class="mdi mdi-filter"></i>Filter</button>
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
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                      <tr v-for="(row, rowIndex) in tableData" :key="rowIndex">
                        <td v-for="(header, colIndex) in tableHeaders" :key="colIndex">
                          {{ row[header] }}
                        </td>
                        <td>
                          <button class="btn btn-sm btn-primary" @click="viewRow(row.teacher_id)">View</button>
                          <router-link :to="`get-single-teachers-print/${row.teacher_id}`" class="btn btn-info btn-sm"><i class="mdi mdi-printer"></i> Print</router-link>
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
                          @paginate="query === '' ? teacherWiseAverageRating() : searchData()"
                      ></pagination>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <data-export/>

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
              <tr v-for="(detail, i) in details" :key="detail.evaluation_statement_id" v-if="details.length">
                <th scope="row">{{ ++i }}</th>
                <td>{{ detail.statement }}</td>

                <!-- Boolean Question হলে Yes/No count দেখাবে -->
                <td v-if="detail.question_type === 'boolean'">
                  Yes: {{ detail.yes_count }} | No: {{ detail.no_count }}
                </td>

                <!-- Rating Question হলে Answer দেখাবে -->
                <td v-else>
                  {{ detail.answer }}
                </td>

                <!-- Total Rating -->
                <td v-if="detail.question_type !== 'boolean'">
                  {{ detail.total_rating }}
                </td>
                <td v-else>-</td>
              </tr>

              <!-- Total Row (শুধু rating প্রশ্নগুলোর জন্য যোগফল দেখাবে) -->
              <tr v-if="details.some(d => d.question_type !== 'boolean')">
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
      details: [],
      pagination: {
        current_page: 1,
        from: 1,
        to: 1,
        total: 1,
        last_page: 1,
      },
      query: "",
      editMode: false,
      isLoading: false,
      from_date: '',
      to_date: '',
      sessionId: '',
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.teacherWiseAverageRating();
      } else {
        this.searchData();
      }
    }
  },
  computed: {
    ratingSum() {
      const sum = this.details
          .filter(q => q.question_type === 'rating')
          .reduce((total, q) => total + Number(q.answer), 0);
      return Math.round(sum);
    },
    ratingSumTotal() {
      return this.details
          .filter(q => q.question_type === 'rating')
          .reduce((sum, q) => sum + Number(q.total_rating), 0);
    }
  },
  mounted() {
    document.title = 'Teacher wise average rating | Survey';
    this.teacherWiseAverageRating();
  },
  methods: {
    teacherWiseAverageRating(){
      let fromdate =  this.from_date ? moment(this.from_date).format('YYYY-MM-DD') : '';
      let todate =  this.to_date ? moment(this.to_date).format('YYYY-MM-DD') : '';
      axios.get(baseurl + 'api/teacher-wise-average-rating?from_date='+ fromdate
          + "&to_date=" + todate
      ).then((response)=>{
        console.log(response.data)
        this.tableData = response.data.teacherReport.data;
        if (this.tableData.length > 0) {
          this.tableHeaders = Object.keys(this.tableData[0]);
        }
        this.pagination.current_page = response.data.teacherReport.current_page;
        this.pagination.from = response.data.teacherReport.from;
        this.pagination.to = response.data.teacherReport.to;
        this.pagination.total = response.data.teacherReport.total;
        this.pagination.last_page = response.data.teacherReport.last_page;

      }).catch((error)=>{

      })
    },
    viewRow(teacherId){
      axios.get(baseurl + 'api/get-single-teachers-evaluation?teacherId=' + teacherId).then((response)=>{
        console.log(response)
        this.details = response.data.details;
        $("#StudentModelModal").modal("show");
      }).catch((error)=>{

      })
    },
    exportTeacherWiseAverageRating(){
      axios.get(baseurl + 'api/report/teacher-wise-average-rating?from_date='+ fromdate
          + "&to_date=" + this.to_date
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
    },
    closeModal(){
      $("#StudentModelModal").modal("hide");
    },
  },
}
</script>

<style scoped>

</style>
