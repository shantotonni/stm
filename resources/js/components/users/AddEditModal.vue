<template>
  <div>
    <div class="modal fade" id="add-edit-dept" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font" id="exampleModalLabel">{{ title }}</div>
          </div>
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal" id="form" @submit.prevent="handleSubmit(onSubmit)">
              <div class="modal-body">
                <div class="row">
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Staff ID" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Staff ID</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" @change="loadFromHR"
                               v-model="staffId" placeholder="Staff ID" :disabled="actionType==='edit'" autocomplete="off">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Staff Name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Staff Name</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="name"
                               v-model="staffName" name="staff-name" placeholder="Staff Name"
                               :disabled="actionType==='edit'" readonly>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Designation" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="designation"
                               v-model="designation" name="designation" placeholder="Designation">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Business" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="business">Business</label>
                        <multiselect v-model="business" :options="businessList" :multiple="false" :close-on-select="true"
                                     :clear-on-select="false" :preserve-search="true" placeholder="Select Business"
                                     label="BusinessName" track-by="Business">

                        </multiselect>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="department" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Department</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="department"
                               v-model="department" name="department" placeholder="Department" readonly>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="email" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}"
                               v-model="email" placeholder="Email">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="mobile" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="mobile">Mobile</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}"
                               v-model="mobile" placeholder="Mobile">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="User Type" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="user-type">User Type</label>
                        <multiselect v-model="userType" :options="roles" :multiple="false" :close-on-select="true"
                                     :clear-on-select="false" :preserve-search="true" placeholder="Select Role"
                                     label="RoleName" track-by="RoleID">

                        </multiselect>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Allowed Business" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="allowed_business">Allowed Business/Function</label>
                        <multiselect v-model="selectedBusiness" :options="businessList" :multiple="false" :close-on-select="true"
                                     :clear-on-select="false" :preserve-search="true" placeholder="Allow Business"
                                     label="BusinessName" track-by="Business">
                        </multiselect>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6">
                    <ValidationProvider name="Allowed Department" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="allowed_department">Allowed Department</label>
                        <multiselect v-model="selectedDepartment" :options="departmentList" :multiple="true" :close-on-select="false"
                                     :clear-on-select="false" :preserve-search="true" placeholder="Allow Department"
                                     label="Department" track-by="DeptCode">

                        </multiselect>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6" v-if="actionType === 'add'">
                    <ValidationProvider name="password" mode="eager" rules="required|min:6"
                                        v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Password</label>
                        <input type="password" class="form-control" :class="{'error-border': errors[0]}" id="password"
                               v-model="password" name="password" placeholder="Password">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-6" v-if="actionType === 'add'">
                    <ValidationProvider name="confirm" mode="eager" rules="required|min:6|confirmed:password"
                                        v-slot="{ errors }">
                      <div class="form-group">
                        <label for="confirm">Confirm</label>
                        <input type="password" class="form-control" :class="{'error-border': errors[0]}" id="confirm"
                               v-model="confirm"
                               name="confirm" placeholder="Confirm Password">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  
                  <div class="col-12">
                    <p class="font-weight-bold">Submenu Permission</p>
                  </div>
                  <div class="col-12 col-md-6" v-for="(submenu,index) in allSubMenu" :key="index">
                      <div class="form-group">
                        <div class="form-check">
                          <p>{{submenu.MenuName}}</p>
                          <div v-for="(sub,index2) in submenu.all_sub_menus" :key="index2">
                            <input class="form-check-input" type="checkbox" :value="sub.SubMenuID" v-model="allSubMenuId" :id="'allSubMenu'+index">
                            <label class="form-check-label" :for="'allSubMenu'+index+'-'+index2">
                              {{sub.SubMenuName}}
                            </label>
                          </div>
                        </div>
                      </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <submit-form v-if="buttonShow" :name="buttonText"/>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import {bus} from "../../app";
import {Common} from "../../mixins/common";
import {mapGetters} from "vuex";

export default {
  mixins: [Common],
  // components: {Multiselect},
  data() {
    return {
      title: '',
      staffId: '',
      staffName: '',
      business: '',
      department: '',
      designation: '',
      buttonText: '',
      mobile: '',
      email: '',
      password: '',
      confirm: '',
      outlet: '',
      userType: '',
      type: 'add',
      actionType: '',
      buttonShow: false,
      selectedBusiness: '',
      businessList: [],
      selectedDepartment: [],
      departmentList: [],
      roles: [],
      allSubMenu: [],
      allSubMenuId: [],
    }
  },
  computed: {},
  created() {
    this.getData();
  },
  mounted() {
    $('#add-edit-dept').on('hidden.bs.modal', () => {
      this.$emit('changeStatus')
    });
    bus.$on('add-edit-user', (row) => {
      if (row) {
        this.selectedBusiness = '';
        this.selectedDepartment = [];
        let instance = this;
        this.axiosGet('user/get-user-info/'+row.StaffID,function(response) {
          var user = response.data;
          instance.title = 'Update User';
          instance.buttonText = "Update";
          instance.staffName = user.StaffName;
          instance.staffId = user.StaffID;
          instance.business = user.business;
          instance.designation = user.Designation;
          instance.department = user.Department;
          instance.mobile = user.Mobile;
          instance.email = user.Email;
          instance.userType = {
            RoleName: user.roles.RoleName,
            RoleID: user.roles.RoleID
          };
          instance.selectedBusiness = response.data.user_business.business;
          response.data.user_department.forEach(function(item){
            instance.selectedDepartment.push(item.department)
          });
          response.data.user_submenu.forEach(function(item) {
            instance.allSubMenuId.push(item.SubMenuID)
          });
          instance.buttonShow = true;
          instance.actionType = 'edit';
        },function(error){

        });
      } else {
        this.title = 'Add User';
        this.buttonText = "Add";
        this.staffId = '';
        this.staffName = '';
        this.designation = '';
        this.business = '';
        this.department = '';
        this.mobile = '';
        this.email = '';
        this.password = '';
        this.userType = '';
        this.selectedBusiness = '';
        this.selectedDepartment = [];
        this.allSubMenu = [];
        this.actionType = 'add'
      }
      $("#add-edit-dept").modal("toggle");
      // $(".error-message").html("");
    })
  },
  methods: {
    getData() {
      let instance = this;
      this.axiosGet('user/modal',function (response) {
        instance.businessList = response.business;
        instance.departmentList = response.department;
        instance.roles = response.roles;
        instance.allSubMenu = response.allSubMenus;
      },function (error) {

      });
    },
    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      let url = '';
      if (this.actionType === 'add') url = 'user/add';
      else url = 'user/update'
      this.axiosPost(url, {
        staffId: this.staffId,
        staffName: this.staffName,
        designation: this.designation,
        business: this.business.Business,
        department: this.department,
        email: this.email,
        mobile: this.mobile,
        userType: this.userType,
        allowedBusiness: this.selectedBusiness,
        allowedDepartment: this.selectedDepartment,
        password: this.password,
        selectedSubMenu: this.allSubMenuId
      }, (response) => {
        this.successNoti(response.message);
        $("#add-edit-dept").modal("toggle");
        bus.$emit('refresh-datatable');
        this.$store.commit('submitButtonLoadingStatus', false);
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      })
    },
    loadFromHR(e) {
      var staffId = e.target.value;
      let instance = this;
      this.axiosGet('user/hr-data?staffId='+staffId,function (response){
        if (response.data.length === 0) {
          instance.staffName = ""
          instance.designation = ""
          instance.department = ""
          instance.buttonShow = false
          instance.errorNoti('No staff found with this staff ID!')
        } else {
          instance.staffName = response.data.Name
          instance.designation = response.data.DesgName
          instance.department = response.data.DeptName
          instance.buttonShow = true
        }
      },function (error){

      });
    }
  }
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
