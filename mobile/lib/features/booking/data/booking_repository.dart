import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';

class BookingRepository {
  final Dio dio = DioClient().dio;

  Future<List<dynamic>> getSlots(int fieldId, String date) async {
    try {
      final response = await dio.get('/fields/$fieldId/slots', queryParameters: {
        'date': date,
      });
      if (response.statusCode == 200) {
        return response.data as List<dynamic>;
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  Future<Map<String, dynamic>?> bookSlot(int slotId, String notes) async {
    try {
      final response = await dio.post('/reservations', data: {
        'time_slot_id': slotId,
        'notes': notes,
      });
      if (response.statusCode == 201 || response.statusCode == 200) {
        return response.data['reservation'];
      }
      return null;
    } on DioException catch (e) {
      if (e.response?.statusCode == 409) {
        throw Exception("Ce créneau n'est plus disponible.");
      }
      throw Exception("Erreur lors de la réservation.");
    } catch (e) {
      throw Exception("Erreur inattendue.");
    }
  }

  Future<bool> payReservation(int reservationId, String method, String phone, String paymentType, {double? customAmount}) async {
    try {
      final data = {
        'method': method,
        'phone': phone,
        'payment_type': paymentType,
      };
      if (paymentType == 'custom' && customAmount != null) {
        data['custom_amount'] = customAmount.toString();
      }
      final response = await dio.post('/reservations/$reservationId/pay', data: data);
      return response.statusCode == 200;
    } on DioException catch (e) {
      if (e.response?.data != null && e.response!.data['message'] != null) {
        throw Exception(e.response!.data['message']);
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<List<dynamic>> getMyReservations() async {
    try {
      final response = await dio.get('/reservations');
      if (response.statusCode == 200) {
        return response.data['data'] as List<dynamic>; // Laravel paginate
      }
      return [];
    } catch (e) {
      return [];
    }
  }
}
