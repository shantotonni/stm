<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Year List']"/>
      <div class="row">
        <div class="col-xl-12">
          <div class="card">
            <div class="datatable" v-if="!isLoading">
              <div class="card-body">
                <div class="d-flex">
                  <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" @click="reload">
                      <i class="fas fa-sync"></i>
                      Reload
                    </button>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped dt-responsive nowrap dataTable no-footer dtr-inline table-sm small">
                    <thead>
                      <tr>
                        <th>SN</th>
                        <th>Name</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(year, i) in years" :key="year.id" v-if="years.length">
                        <th scope="row">{{ ++i }}</th>
                        <td>{{ year.name }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div v-else>
              <skeleton-loader :row="14"/>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {baseurl} from '../../base_url'
export default {
  name: "List",
  data() {
    return {
      years: [],
      query: "",
      editMode: false,
      isLoading: false,
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.getAllYear();
      } else {
        this.searchData();
      }
    }
  },
  mounted() {
    document.title = 'Category List | Bill';
    this.getAllYear();
  },
  methods: {
    getAllYear(){
      this.isLoading = true;
      axios.get(baseurl + 'api/get-all-year').then((response)=>{
        console.log(response)
        this.years = response.data.years;
        this.isLoading = false;
      }).catch((error)=>{

      })
    },
    reload(){
      this.getAllYear();
      this.query = "";
      this.$toaster.success('Data Successfully Refresh');
    },
  },
}
</script>

<style scoped>

</style>
