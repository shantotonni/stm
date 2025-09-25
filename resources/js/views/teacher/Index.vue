<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Teacher List']"/>
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex">
                <div class="flex-grow-1">
                  <div class="row">
                    <div class="col-md-2">
                      <div class="form-group">
                        <input v-model="BMDC_NO" type="text" class="form-control" placeholder="BMDC_NO">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <select name="department_id" id="department_id" class="form-control" v-model="department_id" :class="{ 'is-invalid': form.errors.has('department_id') }">
                          <option disabled value="">Select Department</option>
                          <option :value="department.id" v-for="(department , index) in departments" :key="index">{{ department.name }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <button type="submit" @click="getAllTeacher" class="btn btn-success"><i class="mdi mdi-filter"></i>Filter</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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
                    <button type="button" class="btn btn-success btn-sm" @click="createTeacherModel">
                      <i class="fas fa-plus"></i>
                      Add Teacher
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
                        <th>Teacher name</th>
                        <th>BMDC NO</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Is Head</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(teacher, i) in teachers" :key="teacher.student_id" v-if="teachers.length">
                        <th scope="row">{{ ++i }}</th>
                        <td>{{ teacher.name }}</td>
                        <td>{{ teacher.BMDC_NO }}</td>
                        <td>{{ teacher.designation_name }}</td>
                        <td>{{ teacher.department_name }}</td>
                        <td>{{ teacher.mobile }}</td>
                        <td>{{ teacher.email }}</td>
                        <td>{{ teacher.status }}</td>
                        <td>{{ teacher.is_head }}</td>
                        <td>
                          <button @click="edit(teacher)" class="btn btn-success btn-sm"><i class="far fa-edit"></i></button>
                          <button @click="destroy(teacher.id)" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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
                        @paginate="query === '' ? getAllTeacher() : searchData()"
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
            <h5 class="modal-title mt-0" id="myLargeModalLabel">{{ editMode ? "Edit" : "Add" }} Teacher</h5>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" @click="closeModal">×</button>
          </div>
          <form @submit.prevent="editMode ? update() : store()" @keydown="form.onKeydown($event)">
            <div class="modal-body">
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>BMDC NO</label>
                      <input type="text" name="BMDC_NO" v-model="form.BMDC_NO" class="form-control" :class="{ 'is-invalid': form.errors.has('BMDC_NO') }">
                      <div class="error" v-if="form.errors.has('BMDC_NO')" v-html="form.errors.get('BMDC_NO')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Teacher Name</label>
                      <input type="text" name="name" v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                      <div class="error" v-if="form.errors.has('name')" v-html="form.errors.get('name')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Mobile</label>
                      <input type="text" name="name" v-model="form.mobile" class="form-control" :class="{ 'is-invalid': form.errors.has('mobile') }">
                      <div class="error" v-if="form.errors.has('mobile')" v-html="form.errors.get('mobile')" />
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
                      <label>Select Designation</label>
                      <select name="designation_id" id="designation_id" class="form-control" v-model="form.designation_id" :class="{ 'is-invalid': form.errors.has('designation_id') }">
                        <option disabled value="">Select Designation</option>
                        <option :value="designation.id" v-for="(designation , index) in designations" :key="index">{{ designation.name }}</option>
                      </select>
                      <div class="error" v-if="form.errors.has('designation_id')" v-html="form.errors.get('designation_id')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Select Department</label>
                      <select name="department_id" id="department_id" class="form-control" v-model="form.department_id" :class="{ 'is-invalid': form.errors.has('department_id') }">
                        <option disabled value="">Select Department</option>
                        <option :value="department.id" v-for="(department , index) in departments" :key="index">{{ department.name }}</option>
                      </select>
                      <div class="error" v-if="form.errors.has('department_id')" v-html="form.errors.get('department_id')" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Is Head ? </label>
                      <select name="is_head" id="is_head" class="form-control" v-model="form.is_head" :class="{ 'is-invalid': form.errors.has('is_head') }">
                        <option value="Y">Yes</option>
                        <option value="N">N</option>
                      </select>
                      <div class="error" v-if="form.errors.has('is_head')" v-html="form.errors.get('is_head')" />
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
      teachers: [],
      designations: [],
      departments: [],
      subjects: [],
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
        user_id :'',
        BMDC_NO :'',
        name :'',
        email :'',
        mobile :'',
        department_id :'',
        designation_id:'',
        is_head:'',
      }),
      department_id : '',
      BMDC_NO : ''
    }
  },
  watch: {
    query: function(newQ, old) {
      if (newQ === "") {
        this.getAllTeacher();
      } else {
        this.searchData();
      }
    }
  },
  mounted() {
    document.title = 'Teacher List | Survey';
    this.getAllTeacher();
    this.getAllDepartment();
    this.getAllDesignation();
    this.getAllSubject();
  },
  methods: {
    getAllTeacher(){
      axios.get(baseurl+ 'api/teachers?page='+ this.pagination.current_page
          + "&department_id="+ this.department_id
          + "&BMDC_NO="+ this.BMDC_NO
      ).then((response)=>{
        this.teachers = response.data.data;
        this.pagination = response.data.meta;
      }).catch((error)=>{

      })
    },
    searchData(){
      axios.get(baseurl+"api/search/student/" + this.query + "?page=" + this.pagination.current_page).then(response => {
        this.teachers = response.data.data;
        this.pagination = response.data.meta;
      }).catch(e => {
        this.isLoading = false;
      });
    },
    reload(){
      this.getAllTeacher();
      this.query = "";
      this.$toaster.success('Data Successfully Refresh');
    },
    closeModal(){
      $("#StudentModelModal").modal("hide");
    },
    createTeacherModel(){
      this.editMode = false;
      this.form.reset();
      this.form.clear();
      $("#StudentModelModal").modal("show");
    },
    store(){
      this.form.busy = true;
      this.form.post(baseurl+ "api/teachers").then(response => {
        console.log(response)
        $("#StudentModelModal").modal("hide");
        this.getAllTeacher();
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
      this.form.put(baseurl+"api/teachers/" + this.form.user_id).then(response => {
        $("#StudentModelModal").modal("hide");
        this.getAllTeacher();
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
          axios.delete(baseurl+'api/teachers/'+ id).then((response)=>{
            this.getAllTeacher();
            Swal.fire(
                'Deleted!',
                'Your file has been deleted.',
                'success'
            )
          })
        }
      })
    },
    getAllDepartment(){
      axios.get(baseurl+'api/get-all-department').then((response)=>{
        this.departments = response.data.departments;
      }).catch((error)=>{

      })
    },
    getAllDesignation(){
      axios.get(baseurl+'api/get-all-designation').then((response)=>{
        this.designations = response.data.designations;
      }).catch((error)=>{

      })
    },
    getAllSubject(){
      axios.get(baseurl+'api/get-all-subject').then((response)=>{
        this.subjects = response.data.subjects;
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
