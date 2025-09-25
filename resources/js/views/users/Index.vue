<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['User List']"/>
      <div class="row">
        <div class="col-xl-12">
          <div class="card">
            <div class="datatable" v-if="!isLoading">
              <div class="card-body">
                <div class="d-flex">
                  <div class="flex-grow-1">
                    <div class="row">
                      <div class="col-md-2">
                        <input v-model="query" type="text" class="form-control" placeholder="Search">
                      </div>
                    </div>
                  </div>
                  <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" @click="createUserModel">
                      <i class="fas fa-plus"></i>
                      Add User
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
                      <th class="text-center">SN</th>
                      <th class="text-center">Name</th>
                      <th class="text-center">Email</th>
                      <th class="text-center">Mobile</th>
                      <th class="text-center">Role</th>
                      <th class="text-center">Status</th>
                      <th class="text-center">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(user, i) in users" :key="user.student_id" v-if="users.length">
                      <th scope="row">{{ ++i }}</th>
                      <td>{{ user.name }}</td>
                      <td>{{ user.email }}</td>
                      <td>{{ user.mobile }}</td>
                      <td>{{ user.role }}</td>
                      <td>
                        <span class="badge badge-success" v-if="user.status==='Y'">Active</span>
                        <span class="badge badge-danger" v-else>Inactive</span>
                      </td>
                      <td class="text-center">
                        <button @click="edit(user)" class="btn btn-success btn-sm"><i class="far fa-edit"></i></button>
                        <!--                                                    <button @click="destroy(service_category.id)" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>-->
                      </td>
                    </tr>
                    </tbody>
                  </table>
                  <br>
                  <pagination
                      v-if="pagination.last_page > 1"
                      :pagination="pagination"
                      :offset="5"
                      @paginate="query === '' ? getAllUser() : searchData()"
                  ></pagination>
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
            <h5 class="modal-title mt-0" id="myLargeModalLabel">{{ editMode ? "Edit" : "Add" }} User</h5>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" @click="closeModal">×</button>
          </div>
          <form @submit.prevent="editMode ? update() : store()" @keydown="form.onKeydown($event)">
            <div class="modal-body">
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Name</label>
                      <input type="text" name="name" v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                      <div class="error" v-if="form.errors.has('name')" v-html="form.errors.get('name')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Email</label>
                      <input type="text" name="email" v-model="form.email" class="form-control" :class="{ 'is-invalid': form.errors.has('email') }">
                      <div class="error" v-if="form.errors.has('email')" v-html="form.errors.get('email')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Mobile</label>
                      <input type="text" name="mobile" v-model="form.mobile" class="form-control" :class="{ 'is-invalid': form.errors.has('mobile') }">
                      <div class="error" v-if="form.errors.has('mobile')" v-html="form.errors.get('mobile')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>User Id</label>
                      <input type="text" name="user_unique_code" v-model="form.user_unique_code" class="form-control" :class="{ 'is-invalid': form.errors.has('user_unique_code') }">
                      <div class="error" v-if="form.errors.has('user_unique_code')" v-html="form.errors.get('user_unique_code')" />
                    </div>
                  </div>
                  <div class="col-md-6" v-if="!editMode">
                    <div class="form-group">
                      <label>Password</label>
                      <input type="password" name="password" v-model="form.password" class="form-control" :class="{ 'is-invalid': form.errors.has('password') }">
                      <div class="error" v-if="form.errors.has('password')" v-html="form.errors.get('password')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Select Role</label>
                      <select name="role_id" id="role_id" class="form-control" v-model="form.role_id" :class="{ 'is-invalid': form.errors.has('role_id') }">
                        <option disabled value="">Select Role</option>
                        <option :value="role.id" v-for="(role , index) in roles" :key="index">{{ role.name }}</option>
                      </select>
                      <div class="error" v-if="form.errors.has('role_id')" v-html="form.errors.get('role_id')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Select Status</label>
                      <select name="status" id="status" class="form-control" v-model="form.status" :class="{ 'is-invalid': form.errors.has('status') }">
                        <option value="Y">Active</option>
                        <option value="N">InActive</option>
                      </select>
                      <div class="error" v-if="form.errors.has('status')" v-html="form.errors.get('status')" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal">Close</button>
              <button :disabled="form.busy" type="submit" class="btn btn-primary">{{ editMode ? "Update" : "Create" }} User</button>
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
      users: [],
      roles: [],
      pagination: {
        current_page: 1
      },
      query: "",
      editMode: false,
      isLoading: false,
      form: new Form({
        user_id :'',
        name:'',
        email:'',
        mobile:'',
        user_unique_code:'',
        password:'',
        role_id:'',
        status:'',
      }),
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.getAllUser();
      } else {
        this.searchData();
      }
    }
  },
  mounted() {
    document.title = 'User List | Bill';
    this.getAllUser();
  },
  methods: {
    getAllUser(){
      this.isLoading = true;
      axios.get(baseurl+ 'api/users?page='+ this.pagination.current_page).then((response)=>{
        this.users = response.data.data;
        this.pagination = response.data.meta;
        this.isLoading = false;
      }).catch((error)=>{

      })
    },
    searchData(){
      axios.get(baseurl+"api/search/users/" + this.query + "?page=" + this.pagination.current_page).then(response => {
        this.users = response.data.data;
        this.pagination = response.data.meta;
      }).catch(e => {
        this.isLoading = false;
      });
    },
    reload(){
      this.getAllUser();
      this.query = "";
      this.$toaster.success('Data Successfully Refresh');
    },
    closeModal(){
      $("#StudentModelModal").modal("hide");
    },
    createUserModel(){
      this.getAllRole();
      this.editMode = false;
      this.form.reset();
      this.form.clear();
      $("#StudentModelModal").modal("show");
    },
    store(){
      this.form.busy = true;
      this.form.post(baseurl+ "api/users").then(response => {
        $("#StudentModelModal").modal("hide");
        this.getAllUser();
      }).catch(e => {
        this.isLoading = false;
      });
    },
    edit(role) {
      this.getAllRole();
      this.editMode = true;
      this.form.reset();
      this.form.clear();
      this.form.fill(role);
      $("#StudentModelModal").modal("show");
    },
    update(){
      this.form.busy = true;
      this.form.put(baseurl+"api/users/" + this.form.user_id).then(response => {
        $("#StudentModelModal").modal("hide");
        this.getAllUser();
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
          axios.delete(baseurl+'api/users/'+ id).then((response)=>{
            this.getAllUser();
            Swal.fire(
                'Deleted!',
                'Your file has been deleted.',
                'success'
            )
          })
        }
      })
    },
    getAllRole(){
      axios.get(baseurl+'api/get-all-role').then((response)=>{
        this.roles = response.data.roles;
      }).catch((error)=>{

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
