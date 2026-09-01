import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../bloc/booking_cubit.dart';

class PaymentPage extends StatefulWidget {
  final Map<String, dynamic> reservation;
  const PaymentPage({Key? key, required this.reservation}) : super(key: key);

  @override
  State<PaymentPage> createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  String _selectedMethod = 'wave';
  // 0 = acompte 50%, 1 = montant custom, 2 = total
  int _paymentOption = 0;
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _customAmountController = TextEditingController();

  double get totalPrice => double.tryParse(widget.reservation['total_price'].toString()) ?? 0.0;
  double get minAmount => totalPrice / 2;

  double get amountToPay {
    if (_paymentOption == 0) return minAmount;
    if (_paymentOption == 2) return totalPrice;
    // custom
    final custom = double.tryParse(_customAmountController.text) ?? minAmount;
    return custom.clamp(minAmount, totalPrice);
  }

  bool get isCustomValid {
    if (_paymentOption != 1) return true;
    final v = double.tryParse(_customAmountController.text) ?? 0;
    return v >= minAmount;
  }

  @override
  void initState() {
    super.initState();
    _customAmountController.text = minAmount.toStringAsFixed(0);
  }

  String get paymentTypeForApi {
    if (_paymentOption == 0) return 'half';
    if (_paymentOption == 2) return 'full';
    return 'custom';
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _customAmountController.dispose();
    super.dispose();
  }

  void _submit(BuildContext context) {
    if (_phoneController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez entrer votre numéro de téléphone.'), backgroundColor: Colors.orange),
      );
      return;
    }
    if (!isCustomValid) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Montant minimum: ${minAmount.toStringAsFixed(0)} FCFA'), backgroundColor: Colors.orange),
      );
      return;
    }
    context.read<BookingCubit>().pay(
      widget.reservation['id'],
      _selectedMethod,
      _phoneController.text.trim(),
      paymentTypeForApi,
      customAmount: _paymentOption == 1 ? amountToPay : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: const Text('Paiement de la réservation'),
        backgroundColor: Colors.green.shade700,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: BlocConsumer<BookingCubit, BookingState>(
        listener: (context, state) {
          if (state is PaymentSuccess) {
            context.go('/my_reservations');
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('✅ Réservation confirmée ! À bientôt sur le terrain.'),
                backgroundColor: Colors.green,
                duration: Duration(seconds: 4),
              ),
            );
          } else if (state is PaymentFailure) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.message), backgroundColor: Colors.red),
            );
          }
        },
        builder: (context, state) {
          final isLoading = state is PaymentLoading;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [

                // ── Récapitulatif réservation ──────────────────────────
                Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Colors.green.shade700, Colors.green.shade500],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    children: [
                      const Icon(Icons.sports_soccer, color: Colors.white, size: 36),
                      const SizedBox(height: 8),
                      Text(
                        widget.reservation['field']?['name'] ?? 'Terrain',
                        style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${widget.reservation['timeSlot']?['date'] ?? ''} • ${widget.reservation['timeSlot']?['start_time']?.toString().substring(0,5) ?? ''} - ${widget.reservation['timeSlot']?['end_time']?.toString().substring(0,5) ?? ''}',
                        style: const TextStyle(color: Colors.white70, fontSize: 14),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _recapItem('Prix total', '${totalPrice.toStringAsFixed(0)} FCFA'),
                          Container(width: 1, height: 40, color: Colors.white30),
                          _recapItem('Acompte min.', '${minAmount.toStringAsFixed(0)} FCFA'),
                          Container(width: 1, height: 40, color: Colors.white30),
                          _recapItem('Reste sur place', '${(totalPrice - amountToPay).toStringAsFixed(0)} FCFA'),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // ── Choisir le montant ─────────────────────────────────
                _sectionCard(
                  title: 'Combien voulez-vous payer maintenant ?',
                  icon: Icons.account_balance_wallet_outlined,
                  child: Column(
                    children: [
                      _paymentOptionTile(
                        index: 0,
                        title: 'Acompte (50%) — Obligatoire minimum',
                        subtitle: '${minAmount.toStringAsFixed(0)} FCFA maintenant · ${minAmount.toStringAsFixed(0)} FCFA sur place',
                        isRecommended: true,
                      ),
                      const SizedBox(height: 8),
                      _paymentOptionTile(
                        index: 2,
                        title: 'Paiement intégral (100%)',
                        subtitle: '${totalPrice.toStringAsFixed(0)} FCFA · Rien à payer sur place',
                      ),
                      const SizedBox(height: 8),
                      _paymentOptionTile(
                        index: 1,
                        title: 'Montant personnalisé',
                        subtitle: 'Entre ${minAmount.toStringAsFixed(0)} et ${totalPrice.toStringAsFixed(0)} FCFA',
                      ),
                      if (_paymentOption == 1) ...[
                        const SizedBox(height: 12),
                        TextField(
                          controller: _customAmountController,
                          keyboardType: TextInputType.number,
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            labelText: 'Montant à payer (min. ${minAmount.toStringAsFixed(0)} FCFA)',
                            suffixText: 'FCFA',
                            filled: true,
                            fillColor: Colors.grey.shade50,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            errorText: isCustomValid ? null : 'Minimum ${minAmount.toStringAsFixed(0)} FCFA',
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // ── Montant à payer maintenant ─────────────────────────
                Container(
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.green.shade200),
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Vous payez maintenant :', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      Text(
                        '${amountToPay.toStringAsFixed(0)} FCFA',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 22, color: Colors.green.shade700),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // ── Moyen de paiement ──────────────────────────────────
                _sectionCard(
                  title: 'Moyen de paiement',
                  icon: Icons.payment_outlined,
                  child: Column(
                    children: [
                      Row(
                        children: [
                          _methodCard('wave', 'Wave', '🌊', Colors.blue),
                          const SizedBox(width: 10),
                          _methodCard('orange_money', 'Orange Money', '🟠', Colors.orange),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          _methodCard('free_money', 'Free Money', '💚', Colors.green),
                          const SizedBox(width: 10),
                          _methodCard('cash', 'Espèces', '💵', Colors.grey),
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // ── Numéro de téléphone ────────────────────────────────
                if (_selectedMethod != 'cash')
                  _sectionCard(
                    title: 'Numéro de téléphone',
                    icon: Icons.phone_outlined,
                    child: TextField(
                      controller: _phoneController,
                      keyboardType: TextInputType.phone,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      decoration: InputDecoration(
                        hintText: 'Ex: 77 000 00 00',
                        filled: true,
                        fillColor: Colors.grey.shade50,
                        prefixIcon: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Text('🇸🇳 +221 ', style: TextStyle(fontSize: 14, color: Colors.grey.shade700)),
                        ),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),

                const SizedBox(height: 24),

                // ── Bouton payer ───────────────────────────────────────
                SizedBox(
                  height: 56,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green.shade600,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: 2,
                    ),
                    onPressed: isLoading ? null : () => _submit(context),
                    child: isLoading
                        ? const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
                              SizedBox(width: 12),
                              Text('Traitement en cours...', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            ],
                          )
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.lock, size: 18),
                              const SizedBox(width: 8),
                              Text(
                                'Payer ${amountToPay.toStringAsFixed(0)} FCFA et confirmer',
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                  ),
                ),

                const SizedBox(height: 12),
                const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.security, size: 14, color: Colors.grey),
                    SizedBox(width: 4),
                    Text('Paiement sécurisé — Annulable avant confirmation', style: TextStyle(fontSize: 11, color: Colors.grey)),
                  ],
                ),
                const SizedBox(height: 24),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _recapItem(String label, String value) {
    return Column(
      children: [
        Text(label, style: const TextStyle(color: Colors.white70, fontSize: 11)),
        const SizedBox(height: 4),
        Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
      ],
    );
  }

  Widget _sectionCard({required String title, required IconData icon, required Widget child}) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(icon, size: 18, color: Colors.green.shade700),
            const SizedBox(width: 8),
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          ]),
          const SizedBox(height: 14),
          child,
        ],
      ),
    );
  }

  Widget _paymentOptionTile({required int index, required String title, required String subtitle, bool isRecommended = false}) {
    final isSelected = _paymentOption == index;
    return GestureDetector(
      onTap: () => setState(() => _paymentOption = index),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.green.shade50 : Colors.grey.shade50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? Colors.green.shade500 : Colors.grey.shade200,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 20, height: 20,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isSelected ? Colors.green.shade600 : Colors.transparent,
                border: Border.all(color: isSelected ? Colors.green.shade600 : Colors.grey.shade400, width: 2),
              ),
              child: isSelected ? const Icon(Icons.check, size: 12, color: Colors.white) : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    Flexible(child: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))),
                    if (isRecommended) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: Colors.orange.shade100, borderRadius: BorderRadius.circular(6)),
                        child: Text('Recommandé', style: TextStyle(fontSize: 9, color: Colors.orange.shade800, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ]),
                  const SizedBox(height: 2),
                  Text(subtitle, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _methodCard(String value, String label, String emoji, Color color) {
    final isSelected = _selectedMethod == value;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedMethod = value),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: isSelected ? color.withOpacity(0.12) : Colors.grey.shade50,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isSelected ? color : Colors.grey.shade200,
              width: isSelected ? 2 : 1,
            ),
          ),
          child: Column(
            children: [
              Text(emoji, style: const TextStyle(fontSize: 24)),
              const SizedBox(height: 4),
              Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: isSelected ? color : Colors.grey.shade700)),
            ],
          ),
        ),
      ),
    );
  }
}



