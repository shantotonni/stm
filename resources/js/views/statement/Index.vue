<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Evaluation Statement List']"/>
      <div class="row">
        <div class="col-xl-12">
          <div class="card">
            <div class="datatable" v-if="!isLoading">
              <div class="card-body">
                <div class="d-flex">
                  <div class="flex-grow-1">
                    <div class="row">
                      <div class="col-md-2">
<!--                        <input v-model="query" type="text" class="form-control" placeholder="Search">-->
                      </div>
                    </div>
                  </div>
                  <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" @click="createStatementModel">
                      <i class="fas fa-plus"></i>
                      Add Statement
                    </button>
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
                        <th>Statement</th>
                        <th>Ordering</th>
                        <th>Is Active</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(statement, i) in statements" :key="statement.id" v-if="statements.length">
                        <th scope="row">{{ ++i }}</th>
                        <td>{{ statement.statement }}</td>
                        <td>{{ statement.ordering }}</td>
                        <td>{{ statement.is_active }}</td>
                        <td>
                          <button @click="edit(statement)" class="btn btn-success btn-sm"><i class="far fa-edit"></i></button>
                          <button @click="destroy(statement.id)" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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
                        @paginate="query === '' ? getAllStatement() : searchData()"
                    ></pagination>
                  </div>
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
    <!--  Modal content for the above example -->
    <div class="modal fade" id="StudentModelModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title mt-0" id="myLargeModalLabel">{{ editMode ? "Edit" : "Add" }} Statement</h5>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" @click="closeModal">×</button>
          </div>
          <form @submit.prevent="editMode ? update() : store()" @keydown="form.onKeydown($event)">
            <div class="modal-body">
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Statement</label>
                      <input type="text" name="statement" v-model="form.statement" class="form-control" :class="{ 'is-invalid': form.errors.has('statement') }">
                      <div class="error" v-if="form.errors.has('statement')" v-html="form.errors.get('statement')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Ordering</label>
                      <input type="number" name="ordering" v-model="form.ordering" class="form-control" :class="{ 'is-invalid': form.errors.has('ordering') }">
                      <div class="error" v-if="form.errors.has('ordering')" v-html="form.errors.get('ordering')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Active</label>
                      <select name="is_active" id="is_active" v-model="form.is_active" class="form-control">
                        <option value="Y">Active</option>
                        <option value="N">InActive</option>
                      </select>
                      <div class="error" v-if="form.errors.has('is_active')" v-html="form.errors.get('is_active')" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal">Close</button>
              <button :disabled="form.busy" type="submit" class="btn btn-primary">{{ editMode ? "Update" : "Create" }} Teacher</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';
import moment from "moment";
import {baseurl} from '../../base_url'
export default {
  name: "List",
  components: {
    Datepicker
  },
  data() {
    return {
      statements: [],
      pagination: {
        current_page: 1,
        from: 1,
        to: 1,
        total: 1,
      },
      query: "",
      editMode: false,
      isLoading: false,
      form: new Form({
        id :'',
        statement :'',
        ordering :'',
        is_active :'',
      }),
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.getAllStatement();
      } else {
        this.searchData();
      }
    }
  },
  mounted() {
    document.title = 'Statements List | Survey';
    this.getAllStatement();
  },
  methods: {
    getAllStatement(){
      axios.get(baseurl+ 'api/statements?page='+ this.pagination.current_page).then((response)=>{
        this.statements = response.data.data;
        this.pagination = response.data.meta;
      }).catch((error)=>{

      })
    },
    searchData(){
      axios.get(baseurl+"api/search/statements/" + this.query + "?page=" + this.pagination.current_page).then(response => {
        this.teachers = response.data.data;
        this.pagination = response.data.meta;
      }).catch(e => {
        this.isLoading = false;
      });
    },
    reload(){
      this.getAllStatement();
      this.query = "";
      this.$toaster.success('Data Successfully Refresh');
    },
    closeModal(){
      $("#StudentModelModal").modal("hide");
    },
    createStatementModel(){
      this.editMode = false;
      this.form.reset();
      this.form.clear();
      $("#StudentModelModal").modal("show");
    },
    store(){
      this.form.busy = true;
      this.form.post(baseurl+ "api/statements").then(response => {
        $("#StudentModelModal").modal("hide");
        this.getAllStatement();
      }).catch(e => {
        this.$toaster.error('Already Added');
        this.isLoading = false;
      });
    },
    edit(role) {
      this.editMode = true;
      this.form.reset();
      this.form.clear();
      this.form.fill(role);
      $("#StudentModelModal").modal("show");
    },
    update(){
      this.form.busy = true;
      this.form.put(baseurl+"api/statements/" + this.form.id).then(response => {
        $("#StudentModelModal").modal("hide");
        this.getAllStatement();
      }).catch(e => {
        this.isLoading = false;
      });
    },
    destroy(id){
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(baseurl+'api/statements/'+ id).then((response)=>{
            this.getAllStatement();
            Swal.fire(
                'Deleted!',
                'Your file has been deleted.',
                'success'
            )
          })
        }
      })
    },
    customFormatter(date) {
      return moment(date).format('YYYY-MM-DD');
    }
  },
}
</script>

<style scoped>

</style>
