<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Question wise analysis']">

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
                          <button type="submit" @click="questionWiseAnalysis" class="btn btn-success"><i class="mdi mdi-filter"></i>Filter</button>
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
                      </tr>
                      </thead>
                      <tbody>
                      <tr v-for="(row, rowIndex) in tableData" :key="rowIndex">
                        <td v-for="(header, colIndex) in tableHeaders" :key="colIndex">
                          {{ row[header] }}
                        </td>
                      </tr>
                      </tbody>
                    </table>
                  </div>
                  <!--                  <div class="row">-->
                  <!--                    <div class="col-4">-->
                  <!--                      <div class="data-count">-->
                  <!--                        Show {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} rows-->
                  <!--                      </div>-->
                  <!--                    </div>-->
                  <!--                    <div class="col-8">-->
                  <!--                      <pagination-->
                  <!--                          v-if="pagination.last_page > 1"-->
                  <!--                          :pagination="pagination"-->
                  <!--                          :offset="5"-->
                  <!--                          @paginate="query === '' ? teacherWiseAverageRating() : searchData()"-->
                  <!--                      ></pagination>-->
                  <!--                    </div>-->
                  <!--                  </div>-->
                </div>
              </div>
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
      // pagination: {
      //   current_page: 1,
      //   from: 1,
      //   to: 1,
      //   total: 1,
      // },
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
        this.questionWiseAnalysis();
      } else {
        this.searchData();
      }
    }
  },
  mounted() {
    document.title = 'Question wise analysis | Survey';
    this.questionWiseAnalysis();
  },
  methods: {
    questionWiseAnalysis(){
      let fromdate =  this.from_date ? moment(this.from_date).format('YYYY-MM-DD') : '';
      let todate =  this.to_date ? moment(this.to_date).format('YYYY-MM-DD') : '';
      axios.get(baseurl + 'api/question-wise-analysis?from_date='+ fromdate
          + "&to_date=" + todate
      ).then((response)=>{
        this.tableData = response.data.questionReport;
        if (this.tableData.length > 0) {
          this.tableHeaders = Object.keys(this.tableData[0]);
        }
        // this.pagination = response.data.meta;
      }).catch((error)=>{

      })
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
